<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Filter;

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class SelectionFilterType implements FilterTypeInterface
{
    public function filter(
        ListBuilderInterface $listBuilder,
        FieldDescriptorInterface $fieldDescriptor,
        $options
    ): void {
        if (!\is_string($options)) {
            throw new InvalidFilterTypeOptionsException(
                'The SelectionFilterType requires its options to be comma-separated list of IDs'
            );
        }

        $listBuilder->in($fieldDescriptor, \explode(',', $options));
    }

    public static function getDefaultIndexName(): string
    {
        return 'selection';
    }
}
