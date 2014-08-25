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

interface PreviewInterface
{
    /**
     * starts a preview for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $templateKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function start($userId, $contentUuid, $webspaceKey, $templateKey, $languageCode);

    /**
     * stops a preview
     * @param int $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return
     */
    public function stop($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * returns if a preview started for user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @return bool
     */
    public function started($userId, $contentUuid, $webspaceKey, $locale);

    /**
     * saves changes for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $property propertyName which was changed
     * @param mixed $data new data
     * @param string $templateKey template key
     * @return \Sulu\Component\Content\StructureInterface
     */
    public function update($userId, $contentUuid, $webspaceKey, $templateKey, $languageCode, $property, $data);

    /**
     * {@inheritdoc}
     */
    public function updateTemplate($userId, $contentUuid, $templateKey, $webspaceKey, $languageCode);

    /**
     * returns pending changes for given user and content
     * @param $userId
     * @param string $contentUuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @throws PreviewNotFoundException
     * @return array
     */
    public function getChanges($userId, $contentUuid, $webspaceKey, $languageCode);

    /**
     * renders a content for given user
     * @param int $userId
     * @param string $contentUuid
     * @param string $templateKey
     * @param string $languageCode
     * @param $webspaceKey
     * @param bool $partial
     * @param string|null $property
     * @return string
     */
    public function render($userId, $contentUuid, $templateKey, $languageCode, $webspaceKey, $partial = false, $property = null);
} 
