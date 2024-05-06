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

class TextFilterType implements FilterTypeInterface
{
    public function filter(
        ListBuilderInterface $listBuilder,
        FieldDescriptorInterface $fieldDescriptor,
        $options
    ): void {
        if (!\is_array($options)) {
            throw new InvalidFilterTypeOptionsException('The TextFilterType requires its options to be an array');
        }

        foreach (\array_keys($options) as $operator) {
            $listBuilderOperator = match ($operator) {
                'eq' => ListBuilderInterface::WHERE_COMPARATOR_EQUAL,
                default => throw new InvalidFilterTypeOptionsException(
                    'The TextFilterType does not support the "' . $operator . '" operator'
                ),
            };

            $listBuilder->where($fieldDescriptor, $options[$operator], $listBuilderOperator);
        }
    }

    public static function getDefaultIndexName(): string
    {
        return 'text';
    }
}
