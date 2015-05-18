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
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Sulu\Component\DocumentManager\Metadata;
use Massive\Bundle\SearchBundle\Search\Document;
use Sulu\Component\Content\Structure\Structure;

/**
 * Provides a Metadata Driver for massive search-bundle
 */
class StructureProvider implements ProviderInterface
{
    const FIELD_STRUCTURE_TYPE = '_structure_type';

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
     * @param Factory $factory
     * @param MetadataFactory $metadataFactory
     * @param StructureFactory $structureFactory
     * @param array $mapping
     */
    public function __construct(
        Factory $factory,
        MetadataFactory $metadataFactory,
        StructureFactory $structureFactory,
        array $mapping = array()
    ) {
        $this->factory = $factory;
        $this->mapping = $mapping;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
    }

    /**
     * loads metadata for a given class if its derived from StructureInterface
     * @param \ReflectionClass $class
     * @throws \InvalidArgumentException
     * @return IndexMetadataInterface|null
     */
    public function getMetadataForObject($object)
    {
        if (!$object instanceof ContentBehavior) {
            return;
        }

        $documentMetadata = $this->metadataFactory->getMetadataForClass(get_class($object));
        $structure = $this->structureFactory->getStructure($documentMetadata->getAlias(), $object->getStructureType());

        return $this->getMetadata($documentMetadata, $structure);
    }

    public function getMetadata(Metadata $documentMetadata, Structure $structure)
    {
        $classMetadata = $this->factory->createClassMetadata($documentMetadata->getClass());
        $class = new \ReflectionClass($documentMetadata->getClass());

        $indexMeta = $this->factory->createIndexMetadata();
        $indexMeta->setIdField($this->factory->createMetadataField('uuid'));
        $indexMeta->setLocaleField($this->factory->createMetadataField('locale'));

        $indexName = 'content';
        $categoryName = 'content';

        foreach ($this->mapping as $className => $mapping) {
            if ($class->name !== $className) {
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
                    'title',
                    array(
                        'type' => 'string',
                        'field' => $this->factory->createMetadataProperty('title'),
                    )
                );
            }
        }

        if ($class->isSubclassOf(WebspaceBehavior::class)) {
            // index the webspace
            $indexMeta->addFieldMapping('webspace_key', array(
                'type' => 'string',
                'index_strategy' => Field::INDEX_STORED_INDEXED,
                'field' => $this->factory->createMetadataProperty('webspaceName'),
            ));
        }

        if ($class->isSubclassOf(WorkflowStageBehavior::class)) {
            $indexMeta->addFieldMapping('state', array(
                'type' => 'string',
                'index_strategy' => Field::INDEX_STORED_INDEXED,
                'field' => $this->factory->createMetadataExpression('object.getWorkflowStage() == 1 ? "test" : "published"'),
            ));
        }

        $indexMeta->addFieldMapping(self::FIELD_STRUCTURE_TYPE, array(
            'type' => 'string',
            'index_strategy' => Field::INDEX_STORED_INDEXED,
            'field' => $this->factory->createMetadataProperty('structureType'),
        ));

        $classMetadata->addIndexMetadata('_default', $indexMeta);

        return $classMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllMetadata()
    {
        $metadatas = array();
        foreach ($this->metadataFactory->getAliases() as $alias) {
            $metadata = $this->metadataFactory->getMetadataForAlias($alias);

            if (!$this->structureFactory->hasStructuresFor($alias)) {
                continue;
            }


            foreach ($this->structureFactory->getStructures($alias) as $structure) {
                $structureMetadata = $this->getMetadata($metadata, $structure);
                $metadatas[] = $structureMetadata;
            }
        }

        return $metadatas;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getMetadataForDocument(Document $document)
    {
        if (!$document->hasField(self::FIELD_STRUCTURE_TYPE)) {
            return null;
        }

        $className = $document->getClass();
        $structureType = $document->getField(self::FIELD_STRUCTURE_TYPE)->getValue();
        $documentMetadata = $this->metadataFactory->getMetadataForClass($className);
        $structure = $this->structureFactory->getStructure($documentMetadata->getAlias(), $structureType);

        return $this->getMetadata($documentMetadata, $structure);
    }

    private function mapProperty(Property $property, $metadata)
    {
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
