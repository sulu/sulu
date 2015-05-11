<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\PropertyInterface;
use Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Metadata\Driver\AdvancedDriverInterface;
use Sulu\Component\Content\Compat\Structure;
use Massive\Bundle\SearchBundle\Search\Field;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Structure\Block;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\ContentInstanceFactory;
use DTL\DecoratorGenerator\DecoratorFactory;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;

/**
 * Provides a Metadata Driver for massive search-bundle
 */
class StructureDriver implements AdvancedDriverInterface
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $mapping;

    /**
     * @var StructureFactory
     */
    private $structureFactory;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var DecoratorFactory
     */
    private $decoratorFactory;

    /**
     * @param Factory $factory
     * @param MetadataFactory $metadataFactory
     * @param StructureFactory $structureFactory
     * @param array $mapping
     */
    public function __construct(
        Factory $factory,
        MetadataFactory $metadataFactory,
        StructureFactory $structureFactory,
        DecoratorFactory $decoratorFactory,
        array $mapping = array()
    ) {
        $this->factory = $factory;
        $this->mapping = $mapping;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->decoratorFactory = $decoratorFactory;
    }

    /**
     * loads metadata for a given class if its derived from StructureInterface
     * @param \ReflectionClass $class
     * @throws \InvalidArgumentException
     * @return IndexMetadataInterface|null
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        if (!ContentInstanceFactory::isWrapped($class->name)) {
            return;
        }

        if (!$class->implementsInterface(ContentBehavior::class)) {
            return;
        }

        $classMetadata = $this->factory->createClassMetadata($class->name);

        $documentMetadata = $this->metadataFactory->getMetadataForClass(ContentInstanceFactory::getRealName($class->name));
        $structureType = ContentInstanceFactory::getStructureType($class->name);
        $structure = $this->structureFactory->getStructure($documentMetadata->getAlias(), $structureType);

        $indexMeta = $this->factory->createIndexMetadata();
        $indexMeta->setIdField($this->factory->createMetadataField('uuid'));
        $indexMeta->setLocaleField($this->factory->createMetadataField('locale'));

        $indexName = 'content';
        $categoryName = 'content';

        foreach ($this->mapping as $className => $mapping) {
            if (!$classMetadata->reflection->isSubclassOf($className)) {
                continue;
            }

            $indexName = $mapping['index'];
            $categoryName = $mapping['category'];
        }

        $indexMeta->setCategoryName($categoryName);
        $indexMeta->setIndexName($indexName);

        foreach ($structure->getModelProperties() as $property) {
            if ($property instanceof Block) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getComponents() as $component) {
                    foreach ($component->getChildren() as $componentProperty) {
                        $propertyMapping->addFieldMapping(
                            'title',
                            array(
                                'type' => 'string',
                                'field' => $this->factory->createMetadataProperty('[' . $componentProperty->getName(). ']')
                            )
                        );
                    }
                }

                $indexMeta->addFieldMapping(
                    $property->getName(),
                    array(
                        'type' => 'complex',
                        'mapping' => $propertyMapping,
                        'field' => $this->getContentField($property)
                    )
                );
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        if ($class->isSubclassOf(ResourceSegmentBehavior::class)) {
            $indexMeta->setUrlField($this->factory->createMetadataField('resourceSegment'));
        }

        if (!$indexMeta->getTitleField()) {
            if ($class->isSubclassOf(TitleBehavior::class)) {
                $indexMeta->setTitleField($this->factory->createMetadataProperty('title'));

                $indexMeta->addFieldMapping(
                    $prop->getName(),
                    array(
                        'type' => 'string',
                        'field' => $this->factory->createMetadataField($prop->getName()),
                        'aggregated' => true,
                        'indexed' => false,
                    )
                );
            }
        }

        if ($class->isSubclassOf(WebspaceBehavior::class)) {
            // index the webspace
            $indexMeta->addFieldMapping('webspace_key', array(
                'type' => 'string',
                'field' => $this->factory->createMetadataProperty('webspaceName'),
            ));
        }

        if ($class->isSubclassOf(WorkflowStageBehavior::class)) {
            $indexMeta->addFieldMapping('state', array(
                'type' => 'string',
                'field' => $this->factory->createMetadataExpression('object.nodeState == 1 ? "test" : "published"'),
            ));
        }

        $classMetadata->addIndexMetadata('_default', $indexMeta);

        return $classMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        $classNames = array();
        foreach ($this->metadataFactory->getAliases() as $alias) {
            $metadata = $this->metadataFactory->getMetadataForAlias($alias);

            if (!$this->structureFactory->hasStructuresFor($alias)) {
                continue;
            }

            foreach ($this->structureFactory->getStructures($alias) as $structure) {
                $targetClassName = ContentInstanceFactory::getTargetClassName(
                    $metadata->getClass(),
                    $structure->getName()
                );

                // ensure that the target class exists
                $this->decoratorFactory->generate(
                    $metadata->getClass(),
                    $targetClassName
                );

                $classNames[] = $targetClassName;
            }
        }

        return $classNames;
    }

    private function mapProperty(Property $property, $metadata)
    {
<<<<<<< HEAD
        if ($property->hasTag('sulu.search.field')) {
            $tag = $property->getTag('sulu.search.field');
            $tagAttributes = $tag->getAttributes();

            if ($metadata instanceof IndexMetadata && isset($tagAttributes['role'])) {
                switch ($tagAttributes['role']) {
                    case 'title':
                        $metadata->setTitleField($this->factory->createMetadataField($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array(
                            'field' => $this->factory->createMetadataField($property->getName()),
                            'type' => 'string',
                            'aggregated' => true,
                            'indexed' => false,
                        ));
                        break;
                    case 'description':
                        $metadata->setDescriptionField($this->factory->createMetadataField($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array(
                            'field' => $this->factory->createMetadataField($property->getName()),
                            'type' => 'string',
                            'aggregated' => true,
                            'indexed' => false,
                        ));
                        break;
                    case 'image':
                        $metadata->setImageUrlField($this->factory->createMetadataField($property->getName()));
                        break;
                    default:
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Unknown search field role "%s", role must be one of "%s"',
                                $tagAttributes['role'],
                                implode(', ', array('title', 'description', 'image'))
                            )
                        );
                }
            } elseif (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
                $metadata->addFieldMapping(
                    $property->getName(),
                    array(
                        'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                        'field' => $this->factory->createMetadataField($property->getName()),
                        'aggregated' => true,
                        'indexed' => false,
                    )
                );
=======
        if (false === $property->hasTag('sulu.search.field')) {
            return;
        }

        $tag = $property->getTag('sulu.search.field');
        $tagAttributes = $tag['attributes'];

        if ($metadata instanceof IndexMetadata && isset($tagAttributes['role'])) {
            switch ($tagAttributes['role']) {
                case 'title':
                    $metadata->setTitleField($this->getContentField($property));
                    $metadata->addFieldMapping($property->getName(), array(
                        'field' => $this->getContentField($property),
                        'type' => 'string',
                    ));
                    break;
                case 'description':
                    $metadata->setDescriptionField($this->getContentField($property));
                    $metadata->addFieldMapping($property->getName(), array(
                        'field' => $this->getContentField($property),
                        'type' => 'string',
                    ));
                    break;
                case 'image':
                    $metadata->setImageUrlField($this->getContentField($property));
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Unknown search field role "%s", role must be one of "%s"',
                            $tagAttributes['role'],
                            implode(', ', array('title', 'description', 'image'))
                        )
                    );
>>>>>>> Search integration
            }

            return;
        }

        if (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
            $metadata->addFieldMapping(
                $property->getName(),
                array(
                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                    'field' => $this->getContentField($property),
                )
            );
        }
    }


    private function getContentField(Property $property)
    {
        $field = $this->factory->createMetadataExpression(sprintf(
            'object.getContent().%s.getValue()', $property->getName()
        ));

        return $field;
    }
}
