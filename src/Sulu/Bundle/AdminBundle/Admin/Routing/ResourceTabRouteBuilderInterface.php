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

interface ResourceTabRouteBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    public function setBackRoute(string $backRoute): self;

    public function setTitleProperty(string $titleProperty): self;

    public function getRoute(): Route;
}
