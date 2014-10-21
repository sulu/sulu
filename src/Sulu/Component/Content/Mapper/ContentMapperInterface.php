<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use PHPCR\Query\QueryResultInterface;
use Sulu\Component\Content\BreadcrumbItemInterface;
use Sulu\Component\Content\StructureInterface;

/**
 * Interface of ContentMapper
 */
interface ContentMapperInterface
{
    /**
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     * @param string $uuid uuid of node if exists
     * @param string $parentUuid uuid of parent node
     * @param int $state state of node
     * @param null $isShadow
     * @param null $shadowBaseLanguage
     *
     * @return StructureInterface
     */
    public function save(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null
    );

    /**
     * save a extension with given name and data to an existing node
     * @param string $uuid
     * @param array $data
     * @param string $extensionName
     * @param string $webspaceKey
     * @param string $languageCode
     * @param integer $userId
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
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     *
     * @throws \PHPCR\ItemExistsException if new title already exists
     * @throws \Exception
     *
     * @return StructureInterface
     */
    public function saveStartPage(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $partialUpdate = true
    );

    /**
     * returns a list of data from children of given node
     * @param string $uuid The uuid of the parent node
     * @param string $webspaceKey The key of the webspace we are loading in
     * @param string $languageCode The requested content language
     * @param int $depth The depth of the search
     * @param bool $flat If true, the result is a flat list
     * @param bool $ignoreExceptions
     * @param bool $excludeGhosts If true ghost pages are also loaded
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
     * returns the data from the given id
     * @param string $uuid UUID of the content
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Read data for given language
     * @param bool $loadGhostContent True if also a ghost page should be returned, otherwise false
     * @return StructureInterface
     */
    public function load($uuid, $webspaceKey, $languageCode, $loadGhostContent = false);

    /**
     * returns the data from the given id
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function loadStartPage($webspaceKey, $languageCode);

    /**
     * returns data from given path
     * @param string $resourceLocator Resource locator
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode
     * @param string $segmentKey
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns the content returned by the given sql2 query as structures
     * @param string $sql2 The query, which returns the content
     * @param string $languageCode The language code
     * @param string $webspaceKey The webspace key
     * @param int $limit Limits the number of returned rows
     * @return StructureInterface[]
     */
    public function loadBySql2($sql2, $languageCode, $webspaceKey, $limit = null);

    /**
     * load tree from root to given path
     * @param string $uuid
     * @param string $languageCode
     * @param string $webspaceKey
     * @param bool $excludeGhost
     * @param bool $loadGhostContent
     * @return StructureInterface[]
     */
    public function loadTreeByUuid(
        $uuid,
        $languageCode,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    );

    /**
     * load tree from root to given path
     * @param string $path
     * @param string $languageCode
     * @param string $webspaceKey
     * @param bool $excludeGhost
     * @param bool $loadGhostContent
     * @return StructureInterface[]
     */
    public function loadTreeByPath(
        $path,
        $languageCode,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    );

    /**
     * load breadcrumb for given uuid in given language
     * @param $uuid
     * @param $languageCode
     * @param $webspaceKey
     * @return BreadcrumbItemInterface[]
     */
    public function loadBreadcrumb($uuid, $languageCode, $webspaceKey);

    /**
     * deletes content with subcontent in given webspace
     * @param string $uuid UUID of content
     * @param string $webspaceKey Key of webspace
     */
    public function delete($uuid, $webspaceKey);

    /**
     * moves given node to a new parent node
     * @param string $uuid
     * @param string $destParentUuid
     * @param integer $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function move($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode);

    /**
     * copies given node to a new parent node
     * @param string $uuid
     * @param string $destParentUuid
     * @param integer $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function copy($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode);

    /**
     * Copies the content from one node from one localization to the other
     * @param string $uuid
     * @param $userId
     * @param $webspaceKey
     * @param $srcLanguageCode
     * @param $destLanguageCode
     * @return StructureInterface
     */
    public function copyLanguage($uuid, $userId, $webspaceKey, $srcLanguageCode, $destLanguageCode);

    /**
     * order node with uuid before the node with beforeUuid
     * !IMPORTANT! both nodes should have the same parent
     * @param string $uuid
     * @param string $beforeUuid
     * @param integer $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $languageCode);

    /**
     * TRUE dont rename pages on save
     * @param boolean $noRenamingFlag
     * @return $this
     */
    public function setNoRenamingFlag($noRenamingFlag);

    /**
     * TRUE ignores mandatory in save
     * @param bool $ignoreMandatoryFlag
     * @return $this
     */
    public function setIgnoreMandatoryFlag($ignoreMandatoryFlag);

    /**
     * converts a query result in a list of arrays
     * @param QueryResultInterface $queryResult
     * @param string $webspaceKey
     * @param string[] $locales
     * @param array $fields
     * @param integer $maxDepth
     * @return array
     */
    public function convertQueryResultToArray(
        QueryResultInterface $queryResult,
        $webspaceKey,
        $locales,
        $fields,
        $maxDepth
    );
}
