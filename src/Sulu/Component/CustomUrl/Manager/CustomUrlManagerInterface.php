<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

/**
 * Interface for custom-url manager.
 */
interface CustomUrlManagerInterface
{
    /**
     * Create a new custom-url with given data.
     *
     * @param string $webspaceKey
     * @param array $data
     * @param string|null $locale
     *
     * @return CustomUrlDocument
     */
    public function create($webspaceKey, array $data, $locale = null);

    /**
     * Returns a list of custom-url data (in a assoc-array).
     *
     * @param string $webspaceKey
     *
     * @return array
     */
    public function readList($webspaceKey);

    /**
     * Returns a single custom-url object identified by uuid.
     *
     * @param string $uuid
     * @param string|null $locale
     *
     * @return CustomUrlDocument
     */
    public function read($uuid, $locale = null);

    /**
     * Returns a single custom-url object identified by url.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return CustomUrlDocument
     */
    public function readByUrl($url, $webspaceKey, $locale = null);

    /**
     * Returns route for a custom-url object.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return RouteDocument
     */
    public function readRouteByUrl($url, $webspaceKey, $locale = null);

    /**
     * Update a single custom-url object identified by uuid with given data.
     *
     * @param string $uuid
     * @param array $data
     * @param string|null $locale
     *
     * @return CustomUrlDocument
     */
    public function update($uuid, array $data, $locale = null);

    /**
     * Delete custom-url identified by uuid.
     *
     * @param string $uuid
     */
    public function delete($uuid);

    /**
     * Delete route of a custom-url.
     *
     * @param string $uuid
     * @param string $webspaceKey
     *
     * @throws CannotDeleteCurrentRouteException
     */
    public function deleteRoute($webspaceKey, $uuid);
}
