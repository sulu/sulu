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
            $from = new \DateTime($options['from']);
            $from = new \DateTime($from->format('Y-m-d 00:00:00'));
            $listBuilder->where(
                $fieldDescriptor,
                $from->format('Y-m-d H:i:s'),
                ListBuilderInterface::WHERE_COMPARATOR_GREATER_THAN
            );
        }

        if (isset($options['to'])) {
            $to = new \DateTime($options['to']);
            $to = new \DateTime($to->format('Y-m-d 23:59:59'));
            $listBuilder->where(
                $fieldDescriptor,
                $to->format('Y-m-d H:i:s'),
                ListBuilderInterface::WHERE_COMPARATOR_LESS_THAN
            );
        }
    }

    public static function getDefaultIndexName(): string
    {
        return 'date';
    }
}
