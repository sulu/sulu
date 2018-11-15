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

interface DatagridRouteBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setTitle(string $title): self;

    /**
     * @param string[] $adapter
     */
    public function addDatagridAdapters(array $adapter): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    public function setDefaultLocale(string $locale): self;

    public function setAddRoute(string $addRoute): self;

    public function setEditRoute(string $editRoute): self;

    public function enableSearching(): self;

    public function disableSearching(): self;

    public function enableMoving(): self;

    public function disableMoving(): self;

    public function getRoute(): Route;
}
