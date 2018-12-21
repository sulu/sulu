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

class RouteBuilderFactory implements RouteBuilderFactoryInterface
{
    public function createDatagridRouteBuilder(string $name, string $path): DatagridRouteBuilderInterface
    {
        return new DatagridRouteBuilder($name, $path);
    }

    public function createResourceTabRouteBuilder(string $name, string $path): ResourceTabRouteBuilderInterface
    {
        return new ResourceTabRouteBuilder($name, $path);
    }

    public function createFormRouteBuilder(string $name, string $path): FormRouteBuilderInterface
    {
        return new FormRouteBuilder($name, $path);
    }
}
