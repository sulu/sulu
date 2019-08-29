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

interface PreviewFormRouteBuilderInterface extends RouteBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setFormKey(string $formKey): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    public function setTabTitle(string $tabTitle): self;

    public function setTabCondition(string $tabCondition): self;

    public function setTabOrder(int $tabOrder): self;

    public function setTabPriority(int $tabPriority): self;

    /**
     * @param string[] $toolbarActions
     */
    public function addToolbarActions(array $toolbarActions): self;

    /**
     * @param string[] $routerAttributesToFormStore
     */
    public function addRouterAttributesToFormStore(array $routerAttributesToFormStore): self;

    /**
     * @param string[] $routerAttributesToEditRoute
     */
    public function addRouterAttributesToEditRoute(array $routerAttributesToEditRoute): self;

    public function setEditRoute(string $editRoute): self;

    public function setBackRoute(string $editRoute): self;

    public function setIdQueryParameter(string $idQueryParameter): self;

    public function setPreviewCondition(string $previewCondition): self;

    public function setTitleVisible(bool $titleVisible): self;

    public function setParent(string $parent): self;
}
