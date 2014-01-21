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
     * @param string $workspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function start($userId, $contentUuid, $workspaceKey, $languageCode);

    /**
     * stops a preview
     * @param int $userId
     * @param string $contentUuid
     */
    public function stop($userId, $contentUuid);

    /**
     * returns if a preview started for user and content
     * @param $userId
     * @param $contentUuid
     * @return bool
     */
    public function started($userId, $contentUuid);

    /**
     * saves changes for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $property propertyName which was changed
     * @param mixed $data new data
     * @return \Sulu\Component\Content\StructureInterface
     * @throws PreviewNotFoundException
     */
    public function update($userId, $contentUuid, $property, $data);

    /**
     * returns pending changes for given user and content
     * @param $userId
     * @param $contentUuid
     * @return array
     */
    public function getChanges($userId, $contentUuid);

    /**
     * renders a content for given user
     * @param int $userId
     * @param string $contentUuid
     * @param bool $partial
     * @param string|null $property
     * @return string
     */
    public function render($userId, $contentUuid, $partial = false, $property = null);
} 
