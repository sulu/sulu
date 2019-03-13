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

class RouteBuilderFactory implements RouteBuilderFactoryInterface
{
    public function createListRouteBuilder(string $name, string $path): ListRouteBuilderInterface
    {
        return new ListRouteBuilder($name, $path);
    }

    public function createFormOverlayListRouteBuilder(string $name, string $path): FormOverlayListRouteBuilderInterface
    {
        return new FormOverlayListRouteBuilder($name, $path);
    }

    public function createFormRouteBuilder(string $name, string $path): FormRouteBuilderInterface
    {
        return new FormRouteBuilder($name, $path);
    }

    public function createResourceTabRouteBuilder(string $name, string $path): ResourceTabRouteBuilderInterface
    {
        return new ResourceTabRouteBuilder($name, $path);
    }

    public function createTabRouteBuilder(string $name, string $path): TabRouteBuilderInterface
    {
        return new TabRouteBuilder($name, $path);
    }

    public function createPreviewFormRouteBuilder(string $name, string $path): PreviewFormRouteBuilderInterface
    {
        return new PreviewFormRouteBuilder($name, $path);
    }
}
