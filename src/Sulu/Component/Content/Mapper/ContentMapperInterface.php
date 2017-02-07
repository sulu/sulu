<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use PHPCR\NodeInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use Sulu\Component\Content\BreadcrumbItemInterface;
use Sulu\Component\Content\Compat\StructureInterface;

/**
 * Interface of ContentMapper.
 *
 * @deprecated Use the DocumentManagerInterface instead
 */
interface ContentMapperInterface
{
    /**
     * save a extension with given name and data to an existing node.
     *
     * @param string $uuid
     * @param array  $data
     * @param string $extensionName
     * @param string $webspaceKey
     * @param string $languageCode
     * @param int    $userId
     *
     * @return StructureInterface
     */
    public function saveExtension(
        $uuid,
        $data,
        $extensionName,
        $webspaceKey,
        $languageCode,
        $userId
    );

    /**
     * returns a list of data from children of given node.
     *
     * @param string $uuid             The uuid of the parent node
     * @param string $webspaceKey      The key of the webspace we are loading in
     * @param string $languageCode     The requested content language
     * @param int    $depth            The depth of the search
     * @param bool   $flat             If true, the result is a flat list
     * @param bool   $ignoreExceptions
     * @param bool   $excludeGhosts    If true ghost pages are also loaded
     *
     * @return StructureInterface[]
     */
    public function loadByParent(
        $uuid,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts = false
    );

    /**
     * returns the data from the given id.
     *
     * @param string $uuid             UUID of the content
     * @param string $webspaceKey      Key of webspace
     * @param string $languageCode     Read data for given language
     * @param bool   $loadGhostContent True if also a ghost page should be returned, otherwise false
     *
     * @return StructureInterface
     */
    public function load($uuid, $webspaceKey, $languageCode, $loadGhostContent = false);

    /**
     * returns the data for the given node.
     *
     * @param NodeInterface $contentNode      The node for which to load the data
     * @param string        $languageCode     The locale
     * @param string        $webspaceKey      Key of the webspace
     * @param bool          $excludeGhost     Do not return Ghost structures (return null instead)
     * @param bool          $loadGhostContent Load ghost content
     * @param bool          $excludeShadow    Do not return shadow structures (return null instead)
     */
    public function loadByNode(
        NodeInterface $contentNode,
        $localization,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false,
        $excludeShadow = true
    );

    /**
     * returns the data from the given id.
     *
     * @param string $webspaceKey  Key of webspace
     * @param string $languageCode Read data for given language
     *
     * @return StructureInterface
     */
    public function loadStartPage($webspaceKey, $languageCode);

    /**
     * returns the content returned by the given sql2 query as structures.
     *
     * @param string $sql2         The query, which returns the content
     * @param string $languageCode The language code
     * @param string $webspaceKey  The webspace key
     * @param int    $limit        Limits the number of returned rows
     *
     * @return StructureInterface[]
     */
    public function loadBySql2($sql2, $languageCode, $webspaceKey, $limit = null);

    /**
     * load Structures for the given QOM\QueryInterface instance.
     *
     * @param QueryInterface $query            The query, which returns the content
     * @param string         $languageCode     The language code
     * @param string         $webspaceKey      The webspace key
     * @param bool           $excludeGhost
     * @param bool           $loadGhostContent
     *
     * @return StructureInterface[]
     */
    public function loadByQuery(QueryInterface $query, $languageCode, $webspaceKey, $excludeGhost = true, $loadGhostContent = false);

    /**
     * load breadcrumb for given uuid in given language.
     *
     * @param $uuid
     * @param $languageCode
     * @param $webspaceKey
     *
     * @return BreadcrumbItemInterface[]
     *
     * @deprecated
     */
    public function loadBreadcrumb($uuid, $languageCode, $webspaceKey);

    /**
     * deletes content with subcontent in given webspace.
     *
     * @param string $uuid        UUID of content
     * @param string $webspaceKey Key of webspace
     */
    public function delete($uuid, $webspaceKey);

    /**
     * Copies the content from one node from one localization to the other.
     *
     * @param string $uuid
     * @param $userId
     * @param $webspaceKey
     * @param $srcLanguageCode
     * @param $destLanguageCodes
     *
     * @return StructureInterface
     */
    public function copyLanguage($uuid, $userId, $webspaceKey, $srcLanguageCode, $destLanguageCodes);

    /**
     * order node with uuid before the node with beforeUuid
     * !IMPORTANT! both nodes should have the same parent.
     *
     * @param string $uuid
     * @param string $beforeUuid
     * @param int    $userId
     * @param string $webspaceKey
     * @param string $languageCode
     *
     * @return StructureInterface
     */
    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $languageCode);

    /**
     * brings a node with a given uuid into a given position.
     *
     * @param string $uuid
     * @param int    $position
     * @param int    $userId
     * @param string $webspaceKey
     * @param string $languageCode
     *
     * @throws \Sulu\Component\Content\Exception\InvalidOrderPositionException
     *                                                                         thrown if position is out of range
     *
     * @return StructureInterface
     */
    public function orderAt($uuid, $position, $userId, $webspaceKey, $languageCode);

    /**
     * Converts a query result in a list of arrays.
     *
     * @param QueryResultInterface $queryResult
     * @param string               $webspaceKey
     * @param string[]             $locales
     * @param array                $fields
     * @param int                  $maxDepth
     *
     * @return array
     */
    public function convertQueryResultToArray(
        QueryResultInterface $queryResult,
        $webspaceKey,
        $locales,
        $fields,
        $maxDepth,
        $onlyPublished = true
    );
}
