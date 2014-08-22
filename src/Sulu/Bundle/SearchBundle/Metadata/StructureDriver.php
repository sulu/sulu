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

