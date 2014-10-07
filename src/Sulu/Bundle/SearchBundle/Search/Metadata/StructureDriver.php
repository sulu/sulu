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
use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Bundle\SearchBundle\Search\SuluSearchEvents;
use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\PropertyInterface;
use Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;

/**
 * Provides a Metadata Driver for massive search-bundle
 * @package Sulu\Bundle\SearchBundle\Metadata
 */
class StructureDriver implements DriverInterface
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(Factory $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
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

        $indexMeta = $this->factory->makeIndexMetadata($class->name);

        $indexMeta->setIndexName('content');
        $indexMeta->setIdField('uuid');
        $indexMeta->setLocaleField('languageCode');

        $allProperties = array();

        foreach ($structure->getProperties(true) as $property) {

            if ($property instanceof BlockProperty) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getTypes() as $type) {
                    foreach ($type->getChildProperties() as $typeProperty) {
                        $this->mapProperty($typeProperty, $propertyMapping);
                    }
                }
                $indexMeta->addFieldMapping($property->getName(), array(
                    'type' => 'complex',
                    'mapping' => $propertyMapping,
                ));
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        if ($structure->hasTag('sulu.rlp')) {
            $prop = $structure->getPropertyByTagName('sulu.rlp');
            $indexMeta->setUrlField($prop->getName());
        }

        if (!$indexMeta->getTitleField()) {
            if ($structure->hasTag('sulu.node.name')) {
                $prop = $structure->getPropertyByTagName('sulu.node.name');
                $indexMeta->setTitleField($prop->getName());

                $indexMeta->addFieldMapping($prop->getName(), array(
                    'type' => 'string',
                ));
            }
        }

        // index the webspace
        $indexMeta->addFieldMapping('webspaceKey', array('type' => 'string'));

        $this->eventDispatcher->dispatch(
            SuluSearchEvents::STRUCTURE_LOAD_METADATA, new StructureMetadataLoadEvent($structure, $indexMeta)
        );

        return $indexMeta;
    }

    private function mapProperty(PropertyInterface $property, $metadata)
    {
        if ($property->hasTag('sulu.search.field')) {
            $tag = $property->getTag('sulu.search.field');
            $tagAttributes = $tag->getAttributes();

            if ($metadata instanceof ClassMetadata && isset($tagAttributes['role'])) {
                switch ($tagAttributes['role']) {
                    case 'title':
                        $metadata->setTitleField($property->getName());
                        $metadata->addFieldMapping($property->getName(), array('type' => 'string'));
                        break;
                    case 'description':
                        $metadata->setDescriptionField($property->getName());
                        $metadata->addFieldMapping($property->getName(), array('type' => 'string'));
                        break;
                    case 'image':
                        $metadata->setImageUrlField($property->getName());
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf(
                            'Unknown search field role "%s", role must be one of "%s"',
                            $tagAttributes['role'], implode(', ', array('title', 'description', 'image'))
                        ));
                }
            } elseif (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
                $metadata->addFieldMapping($property->getName(), array(
                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                ));
            }
        }
    }
}
