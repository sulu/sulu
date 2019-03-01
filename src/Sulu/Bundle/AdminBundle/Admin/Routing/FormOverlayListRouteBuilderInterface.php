<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

interface FormOverlayListRouteBuilderInterface
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
     * @param string[] $toolbarActions
     */
    public function addToolbarActions(array $toolbarActions): self;

    public function setDefaultLocale(string $locale): self;

    public function setBackRoute(string $editRoute): self;

    public function enableSearching(): self;

    public function disableSearching(): self;

    /**
     * @param string[] $routerAttributesToListStore
     */
    public function addRouterAttributesToListStore(array $routerAttributesToListStore): self;

    /**
     * @param string[] $routerAttributesToFormStore
     */
    public function addRouterAttributesToFormStore(array $routerAttributesToFormStore): self;

    public function setParent(string $parent): self;

    public function getRoute(): Route;
}
