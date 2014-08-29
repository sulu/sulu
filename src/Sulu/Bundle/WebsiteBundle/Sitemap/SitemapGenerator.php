<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use Jackalope\Query\Row;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Generates a sitemap structure for xml or html
 * @package Sulu\Bundle\WebsiteBundle\Sitemap
 */
class SitemapGenerator implements SitemapGeneratorInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var string
     */
    private $languageNamespace;

    function __construct(
        SessionManagerInterface $sessionManager,
        StructureManagerInterface $structureManager,
        WebspaceManagerInterface $webspaceManager,
        $languageNamespace
    ) {
        $this->languageNamespace = $languageNamespace;
        $this->sessionManager = $sessionManager;
        $this->structureManager = $structureManager;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAllLocals($webspaceKey, $flat = false)
    {
        $locales = array();
        foreach ($this->webspaceManager->findWebspaceByKey($webspaceKey)->getAllLocalizations() as $localizations) {
            $locales[] = $localizations->getLocalization();
        }

        $builder = new MinimumContentQueryBuilder($this->structureManager, $this->languageNamespace);
        $sql2 = $builder->build($webspaceKey, $locales);

        $query = $this->createSql2Query($sql2);
        $queryResult = $query->execute();

        $result = $this->rowsToList($queryResult, $webspaceKey, $locales);

        if (!$flat) {
            $result = $this->listToTree($result, $webspaceKey);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($webspaceKey, $locale, $flat = false)
    {
        $builder = new MinimumContentQueryBuilder($this->structureManager, $this->languageNamespace);
        $sql2 = $builder->build($webspaceKey, array($locale));

        $query = $this->createSql2Query($sql2);
        $queryResult = $query->execute();

        $result = $this->rowsToList($queryResult, $webspaceKey, array($locale));

        if (!$flat) {
            $result = $this->listToTree($result, $webspaceKey);
        }

        return $result;
    }

    /**
     * converts a query result in a list of arrays
     */
    private function rowsToList(QueryResultInterface $queryResult, $webspaceKey, $locales)
    {
        $result = array();
        foreach ($locales as $locale) {
            $routesPath = $this->sessionManager->getRouteNode($webspaceKey, $locale)->getPath();

            /** @var \Jackalope\Query\Row $row */
            foreach ($queryResult->getRows() as $row) {
                $item = $this->rowToArray($row, $locale, $webspaceKey, $routesPath);

                if (false !== $item && !in_array($item, $result)) {
                    $result[] = $item;
                }
            };
        }

        return $result;
    }

    /**
     * converts a query row to an array
     */
    private function rowToArray(Row $row, $locale, $webspaceKey, $routesPath)
    {
        $uuid = $row->getValue('page.jcr:uuid');

        $templateKey = $row->getValue('page.i18n:' . $locale . '-template');
        if ($templateKey !== '') {
            $changed = $row->getValue('page.i18n:' . $locale . '-changed');
            $nodeType = $row->getValue('page.i18n:' . $locale . '-nodeType');
            $path = $row->getPath('page');

            /** @var StructureInterface $structure */
            $structure = $this->structureManager->getStructure($templateKey);

            $title = $row->getValue(
                'page.' . $this->getTranslatedProperty(
                    $structure->getPropertyByTagName('sulu.node.name'),
                    $locale
                )->getName()
            );

            $url = '';
            // if homepage
            if ($this->sessionManager->getContentNode($webspaceKey)->getPath() === $path) {
                $url = '/';
            } else {
                if ($structure->hasTag('sulu.rlp')) {
                    $property = $structure->getPropertyByTagName('sulu.rlp');

                    if ($property->getContentTypeName() !== 'resource_locator') {
                        $url = $row->getValue(
                            'page.' . $this->getTranslatedProperty(
                                $structure->getPropertyByTagName('sulu.rlp'),
                                $locale
                            )->getName()
                        );
                    }
                }

                try {
                    $routePath = $row->getPath('route');
                    $url = str_replace($routesPath, '', $routePath);
                } catch (\Exception $ex) {
                    // ignore exception because no route node
                }
            }


            return array(
                'uuid' => $uuid,
                'nodeType' => $nodeType,
                'path' => $path,
                'changed' => $changed,
                'title' => $title,
                'url' => $url,
                'locale' => $locale
            );
        }

        return false;
    }

    /**
     * generate a tree of the given data with the path property
     */
    private function listToTree($data, $webspaceKey)
    {
        $map = array();
        $contentsPath = $this->sessionManager->getContentNode($webspaceKey)->getPath();
        foreach ($data as $item) {
            $map[str_replace($contentsPath, 'x', $item['path'])] = $item;
        }

        return $this->explodeTree($map, '/')['children']['x'];
    }

    /**
     * FIXME copied from ContentMapper (extract to a service) and provide load node event
     * returns a sql2 query
     * @param string $sql2 The query, which returns the content
     * @param int $limit Limits the number of returned rows
     * @return QueryInterface
     */
    private function createSql2Query($sql2, $limit = null)
    {
        $queryManager = $this->getSession()->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        if ($limit) {
            $query->setLimit($limit);
        }

        return $query;
    }

    /**
     * Returns a translated property
     * @param PropertyInterface $property
     * @param string $locale
     * @return PropertyInterface
     */
    private function getTranslatedProperty(PropertyInterface $property, $locale)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace);
    }

    /**
     * return session from session manager
     * @return \PHPCR\SessionInterface
     */
    private function getSession()
    {
        return $this->sessionManager->getSession();
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
     * // expects:
     * // Array
     * // (
     * //    [etc] => Array
     * //        (
     * //            [php5] => Array
     * //                (
     * //                    [__base_val] => /etc/php5
     * //                    [cli] => Array
     * //                        (
     * //                            [__base_val] => /etc/php5/cli
     * //                            [conf.d] => /etc/php5/cli/conf.d
     * //                            [php.ini] => /etc/php5/cli/php.ini
     * //                        )
     * //
     * //                    [conf.d] => Array
     * //                        (
     * //                            [__base_val] => /etc/php5/conf.d
     * //                            [mysqli.ini] => /etc/php5/conf.d/mysqli.ini
     * //                            [curl.ini] => /etc/php5/conf.d/curl.ini
     * //                            [snmp.ini] => /etc/php5/conf.d/snmp.ini
     * //                            [gd.ini] => /etc/php5/conf.d/gd.ini
     * //                        )
     * //
     * //                    [apache2] => Array
     * //                        (
     * //                            [__base_val] => /etc/php5/apache2
     * //                            [conf.d] => /etc/php5/apache2/conf.d
     * //                            [php.ini] => /etc/php5/apache2/php.ini
     * //                        )
     * //
     * //                )
     * //
     * //        )
     * //
     * // )
     * </code>
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
