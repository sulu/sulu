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
     * @param string $workspaceKey Key of portal
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     * @param string $parentUuid uuid of parent node
     * @param string $uuid uuid of node if exists
     *
     * @throws \PHPCR\ItemExistsException if new title already exists
     *
     * @return StructureInterface
     */
    public function save(
        $data,
        $templateKey,
        $workspaceKey,
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null
    );

    /**
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $workspaceKey Key of portal
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     *
     * @throws \PHPCR\ItemExistsException if new title already exists
     *
     * @return StructureInterface
     */
    public function saveStartPage(
        $data,
        $templateKey,
        $workspaceKey,
        $languageCode,
        $userId,
        $partialUpdate = true
    );

    /**
     * returns a list of data from children of given node
     * @param $uuid
     * @param $workspaceKey
     * @param $languageCode
     * @param int $depth
     * @param bool $flat
     *
     * @return StructureInterface[]
     */
    public function loadByParent($uuid, $workspaceKey, $languageCode, $depth = 1, $flat = true);

    /**
     * returns the data from the given id
     * @param string $uuid UUID of the content
     * @param string $workspaceKey Key of portal
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function load($uuid, $workspaceKey, $languageCode);

    /**
     * returns the data from the given id
     * @param string $workspaceKey Key of portal
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function loadStartPage($workspaceKey, $languageCode);

    /**
     * returns data from given path
     * @param string $resourceLocator Resource locator
     * @param string $workspaceKey Key of portal
     * @param string $languageCode
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $workspaceKey, $languageCode);

    /**
     * deletes content with subcontent in given portal
     * @param string $uuid UUID of content
     * @param string $workspaceKey Key of portal
     */
    public function delete($uuid, $workspaceKey);
}
