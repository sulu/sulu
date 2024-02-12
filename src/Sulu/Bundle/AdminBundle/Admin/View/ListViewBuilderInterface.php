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

interface ListViewBuilderInterface extends ViewBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setListKey(string $listKey): self;

    public function setUserSettingsKey(string $userSettingsKey): self;

    public function setTitle(string $title): self;

    public function setTabTitle(string $tabTitle): self;

    public function setTabOrder(int $tabOrder): self;

    public function setTabPriority(int $tabPriority): self;

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

    /**
     * @param ListItemAction[] $itemActions
     */
    public function addItemActions(array $itemActions): self;

    public function setDefaultLocale(string $locale): self;

    public function setItemDisabledCondition(string $itemDisabledCondition): self;

    public function setAddView(string $addView): self;

    public function setEditView(string $editView): self;

    public function setBackView(string $editView): self;

    public function enableSearching(): self;

    public function disableSearching(): self;

    public function enableSelection(): self;

    public function disableSelection(): self;

    public function enablePagination(): self;

    public function disablePagination(): self;

    public function enableTabGap(): self;

    public function disableTabGap(): self;

    public function enableColumnOptions(): self;

    public function disableColumnOptions(): self;

    public function enableFiltering(): self;

    public function disableFiltering(): self;

    /**
     * @param array<string, array<string, mixed>> $adapterOptions
     */
    public function addAdapterOptions(array $adapterOptions): self;

    /**
     * @param string[] $routerAttributesToListRequest
     */
    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): self;

    /**
     * @param string[] $routerAttributesToListMetadata
     */
    public function addRouterAttributesToListMetadata(array $routerAttributesToListMetadata): self;

    /**
     * @param array<int|string, mixed> $metadataRequestParameters
     */
    public function addMetadataRequestParameters(array $metadataRequestParameters): self;

    /**
     * @param string[] $resourceStorePropertiesToListRequest
     */
    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): self;

    /**
     * @param string[] $resourceStorePropertiesToListMetadata
     */
    public function addResourceStorePropertiesToListMetadata(array $resourceStorePropertiesToListMetadata): self;

    public function addRequestParameters(array $requestParameters): self;

    /**
     * @param Badge[] $badges
     */
    public function addTabBadges(array $badges): self;
}
