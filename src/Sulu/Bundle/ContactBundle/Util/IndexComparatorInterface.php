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
 * Interface for id comparators.
 */
interface IndexComparatorInterface
{
    /**
     * Compares given ids a and b with their index in ids array.
     *
     * @param mixed $a
     * @param mixed $b
     * @param array $ids
     *
     * @return int
     */
    public function compare($a, $b, array $ids);
}
