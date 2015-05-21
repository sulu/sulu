<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

/**
 * Converts a list of nodes to a tree.
 */
class ListToTreeConverter
{
    /**
     * generate a tree of the given data with the path property.
     *
     * @param array $data
     *
     * @return array
     */
    public function convert($data)
    {
        if (empty($data)) {
            return array();
        }

        $map = array();
        $minDepth = 99;
        foreach ($data as $item) {
            $path = rtrim('/root' . $item['path'], '/');
            $map[$path] = $item;

            $parts = explode('/', $path);
            $parts = array_filter($parts);
            $depth = sizeof($parts);
            if ($minDepth > $depth) {
                $minDepth = $depth;
            }
        }

        uksort(
            $map,
            function ($a, $b) use ($map) {
                $depthDifference = substr_count($a, '/') - substr_count($b, '/');
                if ($depthDifference > 0) {
                    return 1;
                } elseif ($depthDifference < 0) {
                    return -1;
                } else {
                    $aPosition = array_search($a, array_keys($map));
                    $bPosition = array_search($b, array_keys($map));

                    return ($aPosition < $bPosition) ? -1 : 1;
                }
            }
        );

        $tree = $this->explodeTree($map, '/');

        for ($i = 0; $i < $minDepth - 1; $i++) {
            $tree['children'] = array_values($tree['children']);
            if (!array_key_exists('children', $tree) || !array_key_exists(0, $tree['children'])) {
                return array();
            }

            $tree = $tree['children'][0];
        }

        $tree = $this->toArray($tree);

        return $tree['children'];
    }

    private function toArray($tree)
    {
        if (isset($tree['children'])) {
            $tree['children'] = array_values($tree['children']);

            // search for empty nodes
            for ($i = 0; $i < sizeof($tree['children']); $i++) {
                if (array_keys($tree['children'][$i]) === array('children')) {
                    array_splice($tree['children'], $i + 1, 0, $tree['children'][$i]['children']);
                    unset($tree['children'][$i]);
                }
            }

            $tree['children'] = array_values($tree['children']);

            // recursive to array
            for ($i = 0; $i < sizeof($tree['children']); $i++) {
                $tree['children'][$i] = $this->toArray($tree['children'][$i]);
            }
        } else {
            $tree['children'] = array();
        }

        return $tree;
    }

    /**
     * Explode any single-dimensional array into a full blown tree structure,
     * based on the delimiters found in it's keys.
     *
     * The following code block can be utilized by PEAR's Testing_DocTest
     * <code>
     * // Input //
     * $key_files = array(
     *   "/etc/php5" => "/etc/php5",
     *   "/etc/php5/cli" => "/etc/php5/cli",
     *   "/etc/php5/cli/conf.d" => "/etc/php5/cli/conf.d",
     *   "/etc/php5/cli/php.ini" => "/etc/php5/cli/php.ini",
     *   "/etc/php5/conf.d" => "/etc/php5/conf.d",
     *   "/etc/php5/conf.d/mysqli.ini" => "/etc/php5/conf.d/mysqli.ini",
     *   "/etc/php5/conf.d/curl.ini" => "/etc/php5/conf.d/curl.ini",
     *   "/etc/php5/conf.d/snmp.ini" => "/etc/php5/conf.d/snmp.ini",
     *   "/etc/php5/conf.d/gd.ini" => "/etc/php5/conf.d/gd.ini",
     *   "/etc/php5/apache2" => "/etc/php5/apache2",
     *   "/etc/php5/apache2/conf.d" => "/etc/php5/apache2/conf.d",
     *   "/etc/php5/apache2/php.ini" => "/etc/php5/apache2/php.ini"
     * );
     *
     * // Execute //
     * $tree = explodeTree($key_files, "/", true);
     *
     * // Show //
     * print_r($tree);
     *
     * @author  Kevin van Zonneveld &lt;kevin@vanzonneveld.net>
     * @author  Lachlan Donald
     * @author  Takkie
     * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
     * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
     *
     * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
     *
     * @link      http://kevin.vanzonneveld.net/
     *
     * @param array $array
     * @param string $delimiter
     * @param bool $baseval
     *
     * @return array
     */
    private function explodeTree($array, $delimiter = '_', $baseval = false)
    {
        if (!is_array($array)) {
            return false;
        }
        $splitRE = '/' . preg_quote($delimiter, '/') . '/';
        $returnArr = array();
        foreach ($array as $key => $val) {
            // Get parent parts and the current leaf
            $parts = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
            $leafPart = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $parentArr = &$returnArr;
            foreach ($parts as $part) {
                if (isset($parentArr['children'][$part])) {
                    if (!is_array($parentArr['children'][$part])) {
                        if ($baseval) {
                            $parentArr['children'][$part] = array('__base_val' => $parentArr[$part]);
                        } else {
                            $parentArr['children'][$part] = array();
                        }
                    }
                    $parentArr = &$parentArr['children'][$part];
                } else {
                    $parentArr['children'][$part] = array();
                    $parentArr = &$parentArr['children'][$part];
                }
            }

            // Add the final part to the structure
            if (empty($parentArr['children'][$leafPart])) {
                $parentArr['children'][$leafPart] = $val;
            } elseif ($baseval && is_array($parentArr['children'][$leafPart])) {
                $parentArr['children'][$leafPart]['__base_val'] = $val;
            } else {
                $parentArr['children'][$leafPart] = array_merge($val, $parentArr['children'][$leafPart]);
            }
        }

        return $returnArr;
    }
}
