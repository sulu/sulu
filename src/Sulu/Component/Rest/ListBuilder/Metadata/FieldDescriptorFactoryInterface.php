<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

interface FieldDescriptorFactoryInterface
{
    /**
     * @return FieldDescriptorInterface[]|null
     */
    public function getFieldDescriptors(string $listKey): ?array;

    /**
     * If the given field descriptor is a case field and one of its cases
     * references the given entity name, return a simplified (non-case)
     * field descriptor using the other case branch.
     *
     * For non-case field descriptors, returns the original unchanged.
     */
    public function excludeCaseFieldDescriptor(FieldDescriptorInterface $fieldDescriptor, string $entityName): FieldDescriptorInterface;
}
