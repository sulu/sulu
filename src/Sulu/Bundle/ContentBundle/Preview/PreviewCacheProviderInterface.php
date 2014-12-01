<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\StructureInterface;

/**
 * provides a interface to cache preview data
 */
interface PreviewCacheProviderInterface
{
    /**
     * returns TRUE if cache contains the content for user
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return boolean
     */
    public function contains($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * deletes cache for given user
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return boolean
     */
    public function delete($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * clones original node and prepare cache node
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return StructureInterface
     */
    public function warmUp($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * returns cached structure
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return boolean|StructureInterface
     */
    public function fetchStructure($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * saves given structure in cache
     * @param StructureInterface $content
     * @param integer $userId
     * @param string string $contentUuid
     * @param string string $webspaceKey
     * @param string string $locale
     */
    public function saveStructure(StructureInterface $content, $userId, $contentUuid, $webspaceKey, $locale);

    /**
     * returns cached changes
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @param boolean $remove if TRUE remove changes after read (singleton)
     * @return array
     */
    public function fetchChanges($userId, $contentUuid, $webspaceKey, $locale, $remove = true);

    /**
     * save changes in cache
     * @param array $changes
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return array
     */
    public function saveChanges($changes, $userId, $contentUuid, $webspaceKey, $locale);

    /**
     * appends changes to existing changes in cache and returns new array
     * @param array $changes
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return array
     */
    public function appendChanges($changes, $userId, $contentUuid, $webspaceKey, $locale);

    /**
     * changes template of cached node
     * @param string $template
     * @param integer $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     */
    public function updateTemplate($template, $userId, $contentUuid, $webspaceKey, $locale);
}
