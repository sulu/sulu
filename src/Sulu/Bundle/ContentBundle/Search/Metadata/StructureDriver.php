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
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\PropertyInterface;
use Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Metadata\Driver\AdvancedDriverInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Structure;

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var string
     */
    private $mapping;

    /**
     * @param Factory $factory
     * @param EventDispatcherInterface $eventDispatcher
     * @param StructureManagerInterface $structureManager
     * @param string $pageIndexName
     * @param string $snippetIndexName
     */
    public function __construct(
        Factory $factory,
        EventDispatcherInterface $eventDispatcher,
        StructureManagerInterface $structureManager,
        array $mapping = array()
    ) {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
        $this->structureManager = $structureManager;
        $this->mapping = $mapping;
    }

    /**
     * loads metadata for a given class if its derived from StructureInterface
     * @param \ReflectionClass $class
     * @throws \InvalidArgumentException
     * @return IndexMetadataInterface|null
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        if (!$class->implementsInterface('Sulu\Component\Content\StructureInterface')) {
            return;
        }

        if ($class->isAbstract()) {
            return;
        }

        /** @var StructureInterface $structure */
        $structure = $class->newInstance();

        $classMetadata = $this->factory->createClassMetadata($class->name);

        $indexMeta = $this->factory->createIndexMetadata();
        $indexMeta->setIdField($this->factory->createMetadataField('uuid'));
        $indexMeta->setLocaleField($this->factory->createMetadataField('languageCode'));

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

        foreach ($structure->getProperties(true) as $property) {
            if ($property instanceof BlockProperty) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getTypes() as $type) {
                    foreach ($type->getChildProperties() as $typeProperty) {
                        $this->mapProperty($typeProperty, $propertyMapping);
                    }
                }

                $indexMeta->addFieldMapping(
                    $property->getName(),
                    array(
                        'type' => 'complex',
                        'mapping' => $propertyMapping,
                        'field' => $this->factory->createMetadataField($property->getName()),
                    )
                );
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        if ($structure->hasTag('sulu.rlp')) {
            $prop = $structure->getPropertyByTagName('sulu.rlp');
            $indexMeta->setUrlField($this->factory->createMetadataField($prop->getName()));
        }

        if (!$indexMeta->getTitleField()) {
            $prop = $structure->getProperty('title');
            $indexMeta->setTitleField($this->factory->createMetadataField($prop->getName()));

            $indexMeta->addFieldMapping(
                $prop->getName(),
                array(
                    'type' => 'string',
                    'field' => $this->factory->createMetadataField($prop->getName()),
                )
            );
        }

        // index the webspace
        $indexMeta->addFieldMapping('webspace_key', array(
            'type' => 'string',
            'field' => $this->factory->createMetadataProperty('webspaceKey'),
        ));

        $classMetadata->addIndexMetadata('_default', $indexMeta);

        return $classMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        $structures = array_merge(
            $this->structureManager->getStructures(Structure::TYPE_PAGE),
            $this->structureManager->getStructures(Structure::TYPE_SNIPPET)
        );
        $classes = array();

        foreach ($structures as $structure) {
            $classes[] = get_class($structure);
        }

        return $classes;
    }

    private function mapProperty(PropertyInterface $property, $metadata)
    {
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
                        ));
                        break;
                    case 'description':
                        $metadata->setDescriptionField($this->factory->createMetadataField($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array(
                            'field' => $this->factory->createMetadataField($property->getName()),
                            'type' => 'string',
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
                    )
                );
            }
        }
    }
}
