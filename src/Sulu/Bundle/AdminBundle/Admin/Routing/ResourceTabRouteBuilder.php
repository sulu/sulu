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

class ResourceTabRouteBuilder implements ResourceTabRouteBuilderInterface
{
    use RouteBuilderTrait;

    const VIEW = 'sulu_admin.resource_tabs';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): ResourceTabRouteBuilderInterface
    {
        $this->setResourceKeyToRoute($this->route, $resourceKey);

        return $this;
    }

    public function addLocales(array $locales): ResourceTabRouteBuilderInterface
    {
        $this->addLocalesToRoute($this->route, $locales);

        return $this;
    }

    public function setBackRoute(string $backRoute): ResourceTabRouteBuilderInterface
    {
        $this->setBackRoute($this->route, $backRoute);

        return $this;
    }

    public function setTitleProperty(string $titleProperty): ResourceTabRouteBuilderInterface
    {
        $this->route->setOption('titleProperty', $titleProperty);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a ResourceTabs view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a ResourceTabs view needs a ":locale" placeholder in its URL'
                . ' if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a ResourceTabs view needs a ":locale" placeholder in its URL'
                . ' if some "locales" have been set.'
            );
        }

        return clone $this->route;
    }
}
