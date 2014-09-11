<?php

namespace Sulu\Bundle\SearchBundle\Metadata;

use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Metadata\Driver\DriverInterface;
use Metadata\Driver\AbstractFileDriver;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Sulu\Component\Content\StructureInterface;

/**
 * Provides a Metadata Driver for massive search-bundle
 * @package Sulu\Bundle\SearchBundle\Metadata
 */
class StructureDriver implements DriverInterface
{
    /**
     * loads metadata for a given class if its derived from StructureInterface
     * @param \ReflectionClass $class
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

        /** @var StructureInterface $instance */
        $instance = $class->newInstance();
        $meta = new IndexMetadata($class->name);

        $meta->setIndexName('content');
        $meta->setIdField('uuid');

        if ($instance->hasTag('sulu.rlp')) {
            $prop = $instance->getPropertyByTagName('sulu.rlp');
            $meta->setUrlField($prop->getName());
        }

        if ($instance->hasTag('sulu.node.name')) {
            $prop = $instance->getPropertyByTagName('sulu.node.name');
            $meta->setTitleField($prop->getName());
        }

        foreach ($instance->getProperties(true) as $property) {
            if (true === $property->getIndexed()) {
                $meta->addFieldMapping($property->getName(), array(
                    'type' => 'string',
                ));
            }
        }

        return $meta;
    }
}

