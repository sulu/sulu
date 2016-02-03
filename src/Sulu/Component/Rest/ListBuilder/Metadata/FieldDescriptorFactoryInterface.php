<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

interface FieldDescriptorFactoryInterface
{
    /**
     * Return field-descriptors for given class.
     *
     * @param string $className
     *
     * @return FieldDescriptorInterface[]
     */
    public function getFieldDescriptorForClass($className);
}
