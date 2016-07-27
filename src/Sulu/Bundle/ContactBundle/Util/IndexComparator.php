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
 * Compares two ids given.
 */
class IndexComparator implements IndexComparatorInterface
{
    public function compare($a, $b, array $ids)
    {
        $indexA = ($index = array_search($a, $ids)) > -1 ? $index : PHP_INT_MAX;
        $indexB = ($index = array_search($b, $ids)) > -1 ? $index : PHP_INT_MAX;

        return strnatcmp($indexA, $indexB);
    }
}
