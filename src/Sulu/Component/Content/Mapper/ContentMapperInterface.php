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
     * Saves the given data in the content storage
     * @param $data array The data to be saved
     * @param $templateKey string name of template
     * @param string $portalKey key of portal
     * @param $languageCode string Save data for given language
     * @param $userId int The id of the user who saves
     * @return StructureInterface
     */
    public function save($data, $templateKey, $portalKey, $languageCode, $userId);

    /**
     * returns the data from the given id
     * @param $uuid string uuid or path to the content
     * @param string $portalKey key of portal
     * @param $languageCode string read data for given language
     * @return StructureInterface
     */
    public function load($uuid, $portalKey, $languageCode);

    /**
     * returns data from given path
     * @param string $resourceLocator resource locator
     * @param string $portalKey key of portal
     * @param string $languageCode
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $portalKey, $languageCode);
}
