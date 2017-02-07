<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;

/**
 * Interface for preview.
 */
interface PreviewInterface
{
    /**
     * Starts a new preview session.
     *
     * @param string $objectClass Class of object
     * @param string $id Identifier of object
     * @param int $userId
     * @param string $webspaceKey
     * @param string $locale
     * @param array|null $data Initial data will be set on the object
     *
     * @return string Token can be used to reuse this preview-session
     *
     * @throws ProviderNotFoundException
     */
    public function start($objectClass, $id, $userId, $webspaceKey, $locale, array $data = []);

    /**
     * Stops the preview-session and deletes the data.
     *
     * @param string $token To identify the preview-session
     */
    public function stop($token);

    /**
     * Returns true if such a session exists.
     *
     * @param string $token To identify the preview-session
     *
     * @return bool
     */
    public function exists($token);

    /**
     * Updates given data in the preview-session.
     *
     * @param string $token To identify the preview-session
     * @param string $webspaceKey Webspace to render object
     * @param string $locale
     * @param array $data Data which will be updated before re-rendering content
     *
     * @return array Changes for the rendering
     *
     * @throws ProviderNotFoundException
     * @throws TokenNotFoundException
     */
    public function update($token, $webspaceKey, $locale, array $data);

    /**
     * Updates given context and restart preview with given data.
     *
     * @param string $token To identify the preview-session
     * @param string $webspaceKey Webspace to render object
     * @param string $locale
     * @param array $context Contains contextual data to restart preview
     * @param array $data Data which will be updated before re-rendering content
     *
     * @return string Complete html response
     *
     * @throws ProviderNotFoundException
     * @throws TokenNotFoundException
     */
    public function updateContext($token, $webspaceKey, $locale, array $context, array $data);

    /**
     * Returns rendered preview-session.
     *
     * @param string $token To identify the preview-session
     * @param string $webspaceKey Webspace to render object
     * @param string $locale
     *
     * @return string Complete html response
     *
     * @throws ProviderNotFoundException
     * @throws TokenNotFoundException
     */
    public function render($token, $webspaceKey, $locale);
}
