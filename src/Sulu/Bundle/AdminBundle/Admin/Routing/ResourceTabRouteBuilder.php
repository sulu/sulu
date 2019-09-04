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
    use FormRouteBuilderTrait;

    const VIEW = 'sulu_admin.resource_tabs';

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): ResourceTabRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function addLocales(array $locales): ResourceTabRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setBackRoute(string $backRoute): ResourceTabRouteBuilderInterface
    {
        $this->setBackRouteToRoute($this->route, $backRoute);

        return $this;
    }

    public function addRouterAttributesToBackRoute(
        array $routerAttributesToBackRoute
    ): ResourceTabRouteBuilderInterface {
        $this->addRouterAttributesToBackRouteToRoute($this->route, $routerAttributesToBackRoute);

        return $this;
    }

    public function addRouterAttributesToBlacklist(
        array $routerAttributesToBlacklist
    ): ResourceTabRouteBuilderInterface {
        $oldRouterAttributesToBlacklist = $this->route->getOption('routerAttributesToBlacklist');
        $newRouterAttributesToBlacklist = $oldRouterAttributesToBlacklist
            ? array_merge($oldRouterAttributesToBlacklist, $routerAttributesToBlacklist)
            : $routerAttributesToBlacklist;

        $this->route->setOption('routerAttributesToBlacklist', $newRouterAttributesToBlacklist);

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
