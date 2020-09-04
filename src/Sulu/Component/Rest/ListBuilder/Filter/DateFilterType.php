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

class DateFilterType implements FilterTypeInterface
{
    public function filter(
        ListBuilderInterface $listBuilder,
        FieldDescriptorInterface $fieldDescriptor,
        $options
    ): void {
        if (!\is_array($options) || (!isset($options['from']) && !isset($options['to']))) {
            throw new InvalidFilterTypeOptionsException(
                'The DateFilterType requires its options to be an array with a "from" or "to" key!'
            );
        }

        if (isset($options['from'])) {
            $listBuilder->where(
                $fieldDescriptor,
                $options['from'],
                ListBuilderInterface::WHERE_COMPARATOR_GREATER_THAN
            );
        }

        if (isset($options['to'])) {
            $listBuilder->where(
                $fieldDescriptor,
                $options['to'],
                ListBuilderInterface::WHERE_COMPARATOR_LESS_THAN
            );
        }
    }

    public static function getDefaultIndexName(): string
    {
        return 'date';
    }
}
