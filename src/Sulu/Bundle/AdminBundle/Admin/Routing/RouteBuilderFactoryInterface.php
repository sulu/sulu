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

interface RouteBuilderFactoryInterface
{
    public function createListRouteBuilder(string $name, string $path): ListRouteBuilderInterface;

    public function createFormOverlayDatagridRouteBuilder(string $name, string $path): FormOverlayDatagridRouteBuilderInterface;

    public function createFormRouteBuilder(string $name, string $path): FormRouteBuilderInterface;

    public function createPreviewFormRouteBuilder(string $name, string $path): PreviewFormRouteBuilderInterface;

    public function createResourceTabRouteBuilder(string $name, string $path): ResourceTabRouteBuilderInterface;

    public function createTabRouteBuilder(string $name, string $path): TabRouteBuilderInterface;
}
