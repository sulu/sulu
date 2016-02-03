<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Metadata\ClassMetadata as BaseClassMetadata;
use Metadata\MergeableInterface;

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
                $this->addPropertyMetadata(new PropertyMetadata($this->name, $name));
            }

            $this->propertyMetadata[$name]->addMetadata(get_class($propertyMetadata), $propertyMetadata);
        }
    }
}
