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

interface FormOverlayListViewBuilderInterface extends ViewBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setListKey(string $listKey): self;

    public function setFormKey(string $formKey): self;

    public function setTitle(string $title): self;

    public function setTabTitle(string $tabTitle): self;

    public function setAddOverlayTitle(string $addOverlayTitle): self;

    public function setEditOverlayTitle(string $editOverlayTitle): self;

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

    public function setItemDisabledCondition(string $itemDisabledCondition): self;

    public function setBackView(string $editView): self;

    public function enableSearching(): self;

    public function disableSearching(): self;

    /**
     * @param string[] $routerAttributesToListRequest
     */
    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): self;

    /**
     * @param string[] $routerAttributesToFormRequest
     */
    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): self;

    /**
     * @param string[] $resourceStorePropertiesToListRequest
     */
    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): self;

    /**
     * @param string[] $resourceStorePropertiesToFormRequest
     */
    public function addResourceStorePropertiesToFormRequest(array $resourceStorePropertiesToFormRequest): self;

    public function addRequestParameters(array $requestParameters): self;

    public function setOverlaySize(string $overlaySize): self;

    public function addMetadataRequestParameters(array $metadataRequestParameters): self;
}
