<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Metadata\Driver\DriverInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Bundle\SearchBundle\Search\SuluSearchEvents;
use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\PropertyInterface;
use Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Metadata\Driver\AdvancedDriverInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Structure\Snippet;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\Structure;

/**
 * Provides a Metadata Driver for massive search-bundle
 * @package Sulu\Bundle\SearchBundle\Metadata
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
    private $pageIndexName;

    /**
     * @var string
     */
    private $snippetIndexName;

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
        $pageIndexName = 'page',
        $snippetIndexName = 'snippet'
    )
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
        $this->structureManager = $structureManager;
        $this->pageIndexName = $pageIndexName;
        $this->snippetIndexName = $snippetIndexName;
        $this->pageIndexName = $pageIndexName;
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
            return null;
        }

        if ($class->isAbstract()) {
            return null;
        }

        /** @var StructureInterface $structure */
        $structure = $class->newInstance();

        $classMetadata = $this->factory->makeClassMetadata($class->name);

        $indexMeta = $this->factory->makeIndexMetadata();
        $indexMeta->setIdField($this->factory->makeMetadataField('uuid'));
        $indexMeta->setLocaleField($this->factory->makeMetadataField('languageCode'));

        if ($structure instanceof Page) {
            $indexMeta->setCategoryName('page');
            $indexMeta->setIndexName($this->pageIndexName);
        }

        if ($structure instanceof Snippet) {
            $indexMeta->setCategoryName('snippet');
            $indexMeta->setIndexName($this->snippetIndexName);
        }

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
                        'field' => $this->factory->makeMetadataField($property->getName()),
                    )
                );
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        $indexMeta->addFieldMapping(
            $property->getName(),
            array(
                'type' => 'string',
                'field' => $this->factory->makeMetadataField($property->getName()),
            )
        );

        if ($structure->hasTag('sulu.rlp')) {
            $prop = $structure->getPropertyByTagName('sulu.rlp');
            $indexMeta->setUrlField($this->factory->makeMetadataField($prop->getName()));
        }

        if (!$indexMeta->getTitleField()) {
            $prop = $structure->getProperty('title');
            $indexMeta->setTitleField($this->factory->makeMetadataField($prop->getName()));

            $indexMeta->addFieldMapping(
                $prop->getName(),
                array(
                    'type' => 'string',
                    'field' => $this->factory->makeMetadataField($prop->getName())
                )
            );
        }

        // index the webspace
        $indexMeta->addFieldMapping('webspaceKey', array(
            'type' => 'string',
            'field' => $this->factory->makeMetadataField('webspaceKey')
        ));

        $classMetadata->addIndexMetadata('_default', $indexMeta);


        return $classMetadata;
    }

    private function mapProperty(PropertyInterface $property, $metadata)
    {
        if ($property->hasTag('sulu.search.field')) {
            $tag = $property->getTag('sulu.search.field');
            $tagAttributes = $tag->getAttributes();

            if ($metadata instanceof IndexMetadata && isset($tagAttributes['role'])) {
                switch ($tagAttributes['role']) {
                    case 'title':
                        $metadata->setTitleField($this->factory->makeMetadataField($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array(
                            'field' => $this->factory->makeMetadataField($property->getName()), 
                            'type' => 'string',
                        ));
                        break;
                    case 'description':
                        $metadata->setDescriptionField($this->factory->makeMetadataField($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array(
                            'field' => $this->factory->makeMetadataField($property->getName()),
                            'type' => 'string',
                        ));
                        break;
                    case 'image':
                        $metadata->setImageUrlField($this->factory->makeMetadataField($property->getName()));
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
                        'field' => $this->factory->makeMetadataField($property->getName()), 
                    )
                );
            }
        }
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
}
