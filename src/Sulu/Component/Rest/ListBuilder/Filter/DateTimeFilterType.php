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

class DateTimeFilterType implements FilterTypeInterface
{
    public function filter(
        ListBuilderInterface $listBuilder,
        FieldDescriptorInterface $fieldDescriptor,
        $options
    ): void {
        if (!\is_array($options) || (!isset($options['from']) && !isset($options['to']))) {
            throw new InvalidFilterTypeOptionsException(
                'The DateTimeFilterType requires its options to be an array with a "from" or "to" key!'
            );
        }

        if (isset($options['from']) && isset($options['to'])) {
            $from = new \DateTime($options['from']);
            $from = new \DateTime($from->format('Y-m-d H:i:00'));
            $listBuilder->where(
                $fieldDescriptor,
                $from,
                ListBuilderInterface::WHERE_COMPARATOR_GREATER_THAN
            );

            $to = new \DateTime($options['to']);
            $to = new \DateTime($to->format('Y-m-d H:i:59'));
            $listBuilder->where(
                $fieldDescriptor,
                $to,
                ListBuilderInterface::WHERE_COMPARATOR_LESS
            );
        } elseif (isset($options['from']) && !isset($options['to'])) {
            $listBuilder->where(
                $fieldDescriptor,
                $options['from'],
                ListBuilderInterface::WHERE_COMPARATOR_GREATER
            );
        } elseif (!isset($options['from']) && isset($options['to'])) {
            $listBuilder->where(
                $fieldDescriptor,
                $options['to'],
                ListBuilderInterface::WHERE_COMPARATOR_LESS
            );
        }
    }

    public static function getDefaultIndexName(): string
    {
        return 'datetime';
    }
}
