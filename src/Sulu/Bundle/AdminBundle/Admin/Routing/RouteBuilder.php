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

class RouteBuilder implements RouteBuilderInterface
{
    use RouteBuilderTrait;

    public function __construct(string $name, string $path, string $view)
    {
        $this->route = new Route($name, $path, $view);
    }

    public function getRoute(): Route
    {
        return clone $this->route;
    }
}
