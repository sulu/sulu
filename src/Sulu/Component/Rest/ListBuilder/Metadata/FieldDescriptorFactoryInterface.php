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

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * Interface for field-descriptor factory.
 */
interface FieldDescriptorFactoryInterface
{
    /**
     * Return field-descriptors for given class.
     *
     * @param string $className
     * @param array $options
     *
     * @return FieldDescriptorInterface[]
     */
    public function getFieldDescriptorForClass($className, $options = []);
}
