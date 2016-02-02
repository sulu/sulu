<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\Compat\StructureInterface;

/**
 * provides a interface to cache preview data.
 */
interface PreviewCacheProviderInterface
{
    /**
     * returns TRUE if cache contains the content for user.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool
     */
    public function contains($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * deletes cache for given user.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool
     */
    public function delete($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * clones original node and prepare cache node.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool|StructureInterface
     */
    public function warmUp($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * returns cached structure.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool|StructureInterface
     */
    public function fetchStructure($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * saves given structure in cache.
     *
     * @param StructureInterface $content
     * @param int                $userId
     * @param string string      $contentUuid
     * @param string string      $webspaceKey
     * @param string string      $locale
     */
    public function saveStructure(StructureInterface $content, $userId, $contentUuid, $webspaceKey, $locale);

    /**
     * returns cached changes.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @param bool   $remove      if TRUE remove changes after read (singleton)
     *
     * @return array
     */
    public function fetchChanges($userId, $contentUuid, $webspaceKey, $locale, $remove = true);

    /**
     * save changes in cache.
     *
     * @param array  $changes
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return array
     */
    public function saveChanges($changes, $userId, $contentUuid, $webspaceKey, $locale);

    /**
     * appends changes to existing changes in cache and returns new array.
     *
     * @param array  $newChanges
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return array
     */
    public function appendChanges($newChanges, $userId, $contentUuid, $webspaceKey, $locale);

    /**
     * changes template of cached node.
     *
     * @param string $template
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     */
    public function updateTemplate($template, $userId, $contentUuid, $webspaceKey, $locale);
}
