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
                if ($this->reflection->hasProperty($name)) {
                    $this->addPropertyMetadata(new PropertyMetadata($this->name, $name));
                } else {
                    $this->addPropertyMetadata(new VirtualPropertyMetadata($this->name, $name));
                }
            }

            $this->propertyMetadata[$name]->addMetadata(get_class($propertyMetadata), $propertyMetadata);
        }
    }
}
