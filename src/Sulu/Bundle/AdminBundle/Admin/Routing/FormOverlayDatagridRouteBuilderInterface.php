<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

interface FormOverlayDatagridRouteBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setDatagridKey(string $datagridKey): self;

    public function setTitle(string $title): self;

    public function setTabTitle(string $tabTitle): self;

    public function setTabOrder(int $tabOrder): self;

    public function setTabCondition(string $tabCondition): self;

    /**
     * @param string[] $adapter
     */
    public function addDatagridAdapters(array $adapter): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    /**
     * @param string[] $toolbarActions
     */
    public function addToolbarActions(array $toolbarActions): self;

    public function setDefaultLocale(string $locale): self;

    public function setAddFormKey(string $addFormKey): self;

    public function setEditFormKey(string $editFormKey): self;

    public function setBackRoute(string $editRoute): self;

    public function enableSearching(): self;

    public function disableSearching(): self;

    /**
     * @param string[] $routerAttributesToDatagridStore
     */
    public function addRouterAttributesToDatagridStore(array $routerAttributesToDatagridStore): self;

    public function setParent(string $parent): self;

    public function getRoute(): Route;
}
