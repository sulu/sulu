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
     * @param array $data
     * @param string|null $locale
     *
     * @throws TitleAlreadyExistsException
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
    public function findList($webspaceKey, $locale);

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
     * @param string|null $locale
     *
     * @return CustomUrlDocument
     */
    public function find($uuid, $locale = null);

    /**
     * Returns a single custom-url object identified by url.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return CustomUrlDocument
     */
    public function findByUrl($url, $webspaceKey, $locale = null);

    /**
     * Returns a list of custom-url documents which targeting the given page.
     *
     * @param UuidBehavior $page
     *
     * @return CustomUrlDocument[]
     */
    public function findByPage(UuidBehavior $page);

    /**
     * Returns route for a custom-url object.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return RouteDocument
     */
    public function findRouteByUrl($url, $webspaceKey, $locale = null);

    /**
     * Update a single custom-url object identified by uuid with given data.
     *
     * @param string $uuid
     * @param array $data
     * @param string|null $locale
     *
     * @return CustomUrlDocument
     */
    public function save($uuid, array $data, $locale = null);

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
     * @throws RouteNotRemovableException
     *
     * @return CustomUrlDocument
     */
    public function deleteRoute($webspaceKey, $uuid);

    /**
     * Invalidate http-cache for given document.
     *
     * @param CustomUrlDocument $document
     */
    public function invalidate(CustomUrlDocument $document);

    /**
     * Invalidate http-cache for given route-document.
     *
     * @param string $webspaceKey
     * @param RouteDocument $routeDocument
     */
    public function invalidateRoute($webspaceKey, RouteDocument $routeDocument);
}
