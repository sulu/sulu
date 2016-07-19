<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Util;

/**
 * Converts contact ids with format '<group><id>'.
 */
class CustomerIdConverter implements IdConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertIdsToGroupedIds(array $ids, array $default = [])
    {
        $result = $default;

        foreach ($ids as $id) {
            $type = substr($id, 0, 1);
            $value = substr($id, 1);

            if (!isset($result[$type])) {
                $result[$type] = [];
            }

            $result[$type][] = $value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertGroupedIdsToIds(array $groupedIds)
    {
        $result = [];
        foreach ($groupedIds as $name => $ids) {
            foreach ($ids as $id) {
                $result[] = $name . '' . $id;
            }
        }

        return $result;
    }
}
