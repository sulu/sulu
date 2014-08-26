<?php

namespace Sulu\Bundle\SearchBundle\Metadata;

use Metadata\Driver\DriverInterface;
use Metadata\Driver\AbstractFileDriver;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;

class StructureDriver implements DriverInterface
{
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        if (!$class->implementsInterface('Sulu\Component\Content\StructureInterface')) {
            return null;
        }

        if ($class->isAbstract()) {
            return null;
        }

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

