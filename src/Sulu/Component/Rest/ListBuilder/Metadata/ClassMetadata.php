<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Metadata\ClassMetadata as BaseClassMetadata;
use Metadata\MergeableInterface;

/**
 * Container for class-metadata.
 */
class ClassMetadata extends BaseClassMetadata implements MergeableInterface
{
    /**
     * @var PropertyMetadata[]
     */
    public $propertyMetadata = [];

    /**
     * {@inheritdoc}
     */
    public function merge(MergeableInterface $object)
    {
        if (!$object instanceof BaseClassMetadata) {
            return;
        }

        foreach ($object->propertyMetadata as $name => $propertyMetadata) {
            if (!array_key_exists($name, $this->propertyMetadata)) {
                $this->addPropertyMetadata($this->getProperty($name));
            }

            $this->propertyMetadata[$name]->addMetadata(get_class($propertyMetadata), $propertyMetadata);
        }
    }

    /**
     * Returns new property-metadata for given name.
     *
     * @param string $name
     *
     * @return PropertyMetadata
     */
    protected function getProperty($name)
    {
        if ($this->reflection->hasProperty($name)) {
            return new PropertyMetadata($this->name, $name);
        }

        return new VirtualPropertyMetadata($this->name, $name);
    }
}
