<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * Interface for custom-url manager.
 */
interface CustomUrlManagerInterface
{
    /**
     * Create a new custom-url with given data.
     *
     * @param string $webspaceKey
     *
     * @return CustomUrlDocument
     *
     * @throws TitleAlreadyExistsException
     */
    public function create($webspaceKey, array $data);

    /**
     * Returns a list of custom-url data (in a assoc-array).
     *
     * @param string $webspaceKey
     *
     * @return array
     */
    public function findList($webspaceKey);

    /**
     * Returns a list of custom-urls.
     *
     * @param string $webspaceKey
     *
     * @return string[]
     */
    public function findUrls($webspaceKey);

    /**
     * Returns a single custom-url object identified by uuid.
     *
     * @param string $uuid
     *
     * @return CustomUrlDocument
     */
    public function find($uuid);

    /**
     * Returns a single custom-url object identified by url.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string|null $locale
     *
     * @return CustomUrlDocument
     */
    public function findByUrl($url, $webspaceKey, $locale = null);

    /**
     * Returns a list of custom-url documents which targeting the given page.
     *
     * @return CustomUrlDocument[]
     */
    public function findByPage(UuidBehavior $page);

    /**
     * Returns route for a custom-url object.
     *
     * @param string $url
     * @param string $webspaceKey
     *
     * @return RouteDocument
     */
    public function findRouteByUrl($url, $webspaceKey);

    /**
     * Update a single custom-url object identified by uuid with given data.
     *
     * @param string $uuid
     *
     * @return CustomUrlDocument
     */
    public function save($uuid, array $data);

    /**
     * Delete custom-url identified by uuid.
     *
     * @param string $uuid
     *
     * @return CustomUrlDocument
     */
    public function delete($uuid);

    /**
     * Delete route of a custom-url.
     *
     * @param string $uuid
     * @param string $webspaceKey
     *
     * @return CustomUrlDocument
     *
     * @throws RouteNotRemovableException
     */
    public function deleteRoute($webspaceKey, $uuid);

    /**
     * Returns base path to custom-url routes.
     *
     * @return string
     */
    public function getRoutesPath($webspaceKey);
}
