<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

interface ListRouteBuilderInterface extends RouteBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setListKey(string $listKey): self;

    public function setUserSettingsKey(string $userSettingsKey): self;

    public function setTitle(string $title): self;

    public function setTabTitle(string $tabTitle): self;

    public function setTabOrder(int $tabOrder): self;

    public function setTabCondition(string $tabCondition): self;

    /**
     * @param string[] $adapter
     */
    public function addListAdapters(array $adapter): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    /**
     * @param ToolbarAction[] $toolbarActions
     */
    public function addToolbarActions(array $toolbarActions): self;

    public function setDefaultLocale(string $locale): self;

    public function setAddRoute(string $addRoute): self;

    public function setEditRoute(string $editRoute): self;

    public function setBackRoute(string $editRoute): self;

    public function enableSearching(): self;

    public function disableSearching(): self;

    /**
     * @param string[] $routerAttributesToListRequest
     */
    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): self;

    /**
     * @param string[] $routerAttributesToListMetadata
     */
    public function addRouterAttributesToListMetadata(array $routerAttributesToListMetadata): self;

    /**
     * @param string[] $resourceStorePropertiesToListRequest
     */
    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): self;
}
