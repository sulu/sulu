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

interface ResourceTabRouteBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    public function setBackRoute(string $backRoute): self;

    /**
     * @param string[] $routerAttributesToBackRoute
     */
    public function addRouterAttributesToBackRoute(array $routerAttributesToBackRoute): self;

    /**
     * @param string[] $routerAttributesToBlacklist
     */
    public function addRouterAttributesToBlacklist(array $routerAttributesToBlacklist): self;

    public function setTitleProperty(string $titleProperty): self;

    public function getRoute(): Route;
}
