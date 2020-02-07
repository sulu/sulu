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
        if (!is_array($options) || !isset($options['from']) || !isset($options['to'])) {
            throw new InvalidFilterTypeOptionsException(
                'The DateTimeFilterType requires its options to be an array with a "from" and "to" key!'
            );
        }

        $listBuilder->between($fieldDescriptor, [$options['from'], $options['to']]);
    }
}
