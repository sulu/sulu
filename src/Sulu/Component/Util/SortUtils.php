<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Sorting utilities.
 */
final class SortUtils
{
    /**
     * Cannot instantiate this class.
     */
    final private function __construct()
    {
    }

    /**
     * Sort the given array of arrays/objects using paths.
     *
     * e.g.
     *
     *      $data = array(
     *          array('foobar' => 'b'),
     *          array('foobar' => 'a'),
     *      );
     *
     *      SortUtils::multisort($data, '[foobar]', 'asc');
     *
     *      echo $data[0]; // "a"
     *
     * You can also use method names:
     *
     *      SortUtils::multisort($data, 'getFoobar', 'asc');
     *
     * Or sort on multidimensional arrays:
     *
     *      SortUtils::multisort($data, 'foobar.bar.getFoobar', 'asc');
     *
     * And you can sort on multiple paths:
     *
     *      SortUtils::multisort($data, array('foo', 'bar'), 'asc');
     *
     * The path is any path accepted by the property access component:
     *
     * @link http://symfony.com/doc/current/components/property_access/introduction.html
     *
     * @param array        $values
     * @param string|array $path      Path or paths on which to sort on
     * @param string       $direction Direction to sort in (either ASC or DESC)
     *
     * @return array
     */
    public static function multisort($values, $paths, $direction = 'ASC')
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $values = (array) $values;
        $paths = (array) $paths;

        usort($values, function ($a, $b) use ($accessor, $paths) {
            foreach ($paths as $i => $path) {
                $aOrder = $accessor->getValue($a, $path);
                $bOrder = $accessor->getValue($b, $path);

                if (is_string($aOrder)) {
                    $aOrder = strtolower($aOrder);
                    $bOrder = strtolower($bOrder);
                }

                if ($aOrder == $bOrder) {
                    if (count($paths) == ($i + 1)) {
                        return 0;
                    } else {
                        continue;
                    }
                }

                return ($aOrder < $bOrder) ? -1 : 1;
            }
        });

        if (strtoupper($direction) == 'DESC') {
            $values = array_reverse($values);
        }

        return $values;
    }
}
