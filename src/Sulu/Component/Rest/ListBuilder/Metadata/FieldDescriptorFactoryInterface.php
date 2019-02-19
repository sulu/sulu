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
     * @return FieldDescriptorInterface[]
     */
    public function getFieldDescriptors(string $listKey): ?array;
}
