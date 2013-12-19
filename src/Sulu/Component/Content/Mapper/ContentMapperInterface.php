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
     * @param string $portalKey Key of portal
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
        $portalKey,
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null
    );

    /**
     * returns a list of data from children of given node
     * @param $uuid
     * @param $portalKey
     * @param $languageCode@
     *
     * @return StructureInterface[]
     */
    public function loadByParent($uuid, $portalKey, $languageCode);

    /**
     * returns the data from the given id
     * @param string $uuid UUID of the content
     * @param string $portalKey Key of portal
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function load($uuid, $portalKey, $languageCode);

    /**
     * returns data from given path
     * @param string $resourceLocator Resource locator
     * @param string $portalKey Key of portal
     * @param string $languageCode
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $portalKey, $languageCode);

    /**
     * deletes content with subcontent in given portal
     * @param string $uuid UUID of content
     * @param string $portalKey Key of portal
     */
    public function delete($uuid, $portalKey);
}
