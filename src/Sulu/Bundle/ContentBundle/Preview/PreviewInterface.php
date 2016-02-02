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

interface PreviewInterface
{
    /**
     * starts a preview for given user and content.
     *
     * @param int         $userId
     * @param string      $contentUuid
     * @param string      $webspaceKey
     * @param string      $locale
     * @param array       $data        changes which will be set after warmup
     * @param string|null $template
     *
     * @return StructureInterface
     */
    public function start($userId, $contentUuid, $webspaceKey, $locale, $data = null, $template = null);

    /**
     * stops a preview.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return
     */
    public function stop($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * returns if a preview started for user and content.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool
     */
    public function started($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * update properties in changes array.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @param array  $changes
     *
     * @return StructureInterface
     */
    public function updateProperties($userId, $contentUuid, $webspaceKey, $locale, $changes);

    /**
     * saves changes for given user and content.
     *
     * @param int    $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @param string $property    propertyName which was changed
     * @param mixed  $data        new data
     *
     * @return \Sulu\Component\Content\Compat\StructureInterface
     */
    public function updateProperty($userId, $contentUuid, $webspaceKey, $locale, $property, $data);

    /**
     * returns pending changes for given user and content.
     *
     * @param $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     *
     * @throws PreviewNotFoundException
     *
     * @return array
     */
    public function getChanges($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * renders a content for given user.
     *
     * @param int         $userId
     * @param string      $contentUuid
     * @param string      $webspaceKey
     * @param string      $locale
     * @param bool        $partial
     * @param string|null $property
     *
     * @return string
     */
    public function render(
        $userId,
        $contentUuid,
        $webspaceKey,
        $locale,
        $partial = false,
        $property = null
    );
}
