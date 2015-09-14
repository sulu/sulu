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
 * Provides function to sort array by ids array.
 */
trait SortByIdsTrait
{
    /**
     * Sorts list-response by id array.
     *
     * @param array $ids
     * @param array $list
     *
     * @return array
     */
    protected function sortByIds($ids, array $list)
    {
        $result = [];
        for ($i = 0; $i < count($list); $i++) {
            if (false !== ($index = array_search($list[$i]['id'], $ids))) {
                $result[$index] = $list[$i];
            }
        }
        ksort($result);

        return $result;
    }
}
