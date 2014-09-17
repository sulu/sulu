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

use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Converts a list of nodes to a tree
 */
class ListToTreeConverter
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * generate a tree of the given data with the path property
     */
    public function convert($data, $webspaceKey)
    {
        $map = array();
        $contentsPath = $this->sessionManager->getContentNode($webspaceKey)->getPath();
        foreach ($data as $item) {
            $map[str_replace($contentsPath, 'root', $item['path'])] = $item;
        }

        $tree = $this->explodeTree($map, '/');
        if (!array_key_exists('children', $tree)) {
            return array();
        }
        $tree = $tree['children'];

        // root node exists
        if (array_key_exists('root', $tree)) {
            return $tree['root'];
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
     * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
     * @link      http://kevin.vanzonneveld.net/
     *
     * @param array $array
     * @param string $delimiter
     * @param boolean $baseval
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
            $parentArr = & $returnArr;
            foreach ($parts as $part) {
                if (isset($parentArr['children'][$part])) {
                    if (!is_array($parentArr['children'][$part])) {
                        if ($baseval) {
                            $parentArr['children'][$part] = array('__base_val' => $parentArr[$part]);
                        } else {
                            $parentArr['children'][$part] = array();
                        }
                    }
                    $parentArr = & $parentArr['children'][$part];
                }
            }

            // Add the final part to the structure
            if (empty($parentArr['children'][$leafPart])) {
                $parentArr['children'][$leafPart] = $val;
            } elseif ($baseval && is_array($parentArr['children'][$leafPart])) {
                $parentArr['children'][$leafPart]['__base_val'] = $val;
            }
        }

        return $returnArr;
    }
} 
