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

trait RouteBuilderTrait
{
    /**
     * @var Route
     */
    private $route;

    public function setView(string $view): RouteBuilderInterface
    {
        $this->route->setView($view);

        return $this;
    }

    public function setOption(string $key, $value): RouteBuilderInterface
    {
        $this->route->setOption($key, $value);

        return $this;
    }

    public function setAttributeDefault(string $key, string $value): RouteBuilderInterface
    {
        $this->route->setAttributeDefault($key, $value);

        return $this;
    }

    public function setParent(string $parent): RouteBuilderInterface
    {
        $this->route->setParent($parent);

        return $this;
    }

    public function addRerenderAttribute(string $attribute): RouteBuilderInterface
    {
        $this->route->addRerenderAttribute($attribute);

        return $this;
    }
}
