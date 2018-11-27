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

class DatagridRouteBuilder implements DatagridRouteBuilderInterface
{
    const VIEW = 'sulu_admin.datagrid';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): DatagridRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function setTitle(string $title): DatagridRouteBuilderInterface
    {
        $this->route->setOption('title', $title);

        return $this;
    }

    public function addDatagridAdapters(array $datagridAdapters): DatagridRouteBuilderInterface
    {
        $oldDatagridAdapters = $this->route->getOption('adapters');
        $newDatagridAdapters = $oldDatagridAdapters ? array_merge($oldDatagridAdapters, $datagridAdapters) : $datagridAdapters;
        $this->route->setOption('adapters', $newDatagridAdapters);

        return $this;
    }

    public function addLocales(array $locales): DatagridRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setDefaultLocale(string $locale): DatagridRouteBuilderInterface
    {
        $this->route->setAttributeDefault('locale', $locale);

        return $this;
    }

    public function setAddRoute(string $addRoute): DatagridRouteBuilderInterface
    {
        $this->route->setOption('addRoute', $addRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): DatagridRouteBuilderInterface
    {
        $this->route->setOption('editRoute', $editRoute);

        return $this;
    }

    public function enableSearching(): DatagridRouteBuilderInterface
    {
        $this->route->setOption('searchable', true);

        return $this;
    }

    public function disableSearching(): DatagridRouteBuilderInterface
    {
        $this->route->setOption('searchable', false);

        return $this;
    }

    public function enableMoving(): DatagridRouteBuilderInterface
    {
        $this->route->setOption('movable', true);

        return $this;
    }

    public function disableMoving(): DatagridRouteBuilderInterface
    {
        $this->route->setOption('movable', false);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a datagrid view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->route->getOption('adapters')) {
            throw new \DomainException(
                'A route for a datagrid needs a "adapters" option.'
                . ' You have likely forgotten to call the "addDatagridAdapters" method.'
            );
        }

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a datagrid needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a datagrid cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return $this->route;
    }
}
