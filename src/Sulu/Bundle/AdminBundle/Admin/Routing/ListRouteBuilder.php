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

class ListRouteBuilder implements ListRouteBuilderInterface
{
    const VIEW = 'sulu_admin.list';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): ListRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function setListkey(string $listKey): ListRouteBuilderInterface
    {
        $this->route->setOption('listKey', $listKey);

        return $this;
    }

    public function setTitle(string $title): ListRouteBuilderInterface
    {
        $this->route->setOption('title', $title);

        return $this;
    }

    public function setTabTitle(string $tabTitle): ListRouteBuilderInterface
    {
        $this->route->setOption('tabTitle', $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): ListRouteBuilderInterface
    {
        $this->route->setOption('tabOrder', $tabOrder);

        return $this;
    }

    public function setTabCondition(string $tabCondition): ListRouteBuilderInterface
    {
        $this->route->setOption('tabCondition', $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): ListRouteBuilderInterface
    {
        $oldListAdapters = $this->route->getOption('adapters');
        $newListAdapters = $oldListAdapters ? array_merge($oldListAdapters, $listAdapters) : $listAdapters;
        $this->route->setOption('adapters', $newListAdapters);

        return $this;
    }

    public function addLocales(array $locales): ListRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setDefaultLocale(string $locale): ListRouteBuilderInterface
    {
        $this->route->setAttributeDefault('locale', $locale);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): ListRouteBuilderInterface
    {
        $oldToolbarActions = $this->route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $this->route->setOption('toolbarActions', $newToolbarActions);

        return $this;
    }

    public function setAddRoute(string $addRoute): ListRouteBuilderInterface
    {
        $this->route->setOption('addRoute', $addRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): ListRouteBuilderInterface
    {
        $this->route->setOption('editRoute', $editRoute);

        return $this;
    }

    public function setBackRoute(string $backRoute): ListRouteBuilderInterface
    {
        $this->route->setOption('backRoute', $backRoute);

        return $this;
    }

    public function enableSearching(): ListRouteBuilderInterface
    {
        $this->route->setOption('searchable', true);

        return $this;
    }

    public function disableSearching(): ListRouteBuilderInterface
    {
        $this->route->setOption('searchable', false);

        return $this;
    }

    public function enableMoving(): ListRouteBuilderInterface
    {
        $this->route->setOption('movable', true);

        return $this;
    }

    public function disableMoving(): ListRouteBuilderInterface
    {
        $this->route->setOption('movable', false);

        return $this;
    }

    public function addRouterAttributesToListStore(array $routerAttributesToListStore): ListRouteBuilderInterface
    {
        $oldRouterAttributesToListStore = $this->route->getOption('routerAttributesToListStore');
        $newRouterAttributesToListStore = $oldRouterAttributesToListStore ? array_merge($oldRouterAttributesToListStore, $routerAttributesToListStore) : $routerAttributesToListStore;
        $this->route->setOption('routerAttributesToListStore', $newRouterAttributesToListStore);

        return $this;
    }

    public function addResourceStorePropertiesToListStore(array $resourceStorePropertiesToListStore): ListRouteBuilderInterface
    {
        $oldResourceStorePropertiesToListStore = $this->route->getOption('resourceStorePropertiesToListStore');
        $newResourceStorePropertiesToListStore = $oldResourceStorePropertiesToListStore ? array_merge($oldResourceStorePropertiesToListStore, $resourceStorePropertiesToListStore) : $resourceStorePropertiesToListStore;
        $this->route->setOption('resourceStorePropertiesToListStore', $newResourceStorePropertiesToListStore);

        return $this;
    }

    public function setParent(string $parent): ListRouteBuilderInterface
    {
        $this->route->setParent($parent);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a list view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->route->getOption('listKey')) {
            throw new \DomainException(
                'A route for a list view needs a "listKey" option.'
                . ' You have likely forgotten to call the "setListKey" method.'
            );
        }

        if (!$this->route->getOption('adapters')) {
            throw new \DomainException(
                'A route for a list needs a "adapters" option.'
                . ' You have likely forgotten to call the "addListAdapters" method.'
            );
        }

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a list needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a list cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->route;
    }
}
