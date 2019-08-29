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
    use RouteBuilderTrait;
    use ListRouteBuilderTrait;
    use TabRouteBuilderTrait;

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
        $this->setResourceKeyToRoute($this->route, $resourceKey);

        return $this;
    }

    public function setListKey(string $listKey): ListRouteBuilderInterface
    {
        $this->setListKeyToRoute($this->route, $listKey);

        return $this;
    }

    public function setUserSettingsKey(string $userSettingsKey): ListRouteBuilderInterface
    {
        $this->setUserSettingsKeyToRoute($this->route, $userSettingsKey);

        return $this;
    }

    public function setTitle(string $title): ListRouteBuilderInterface
    {
        $this->setTitleToRoute($this->route, $title);

        return $this;
    }

    public function setTabTitle(string $tabTitle): ListRouteBuilderInterface
    {
        $this->setTabTitleToRoute($this->route, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): ListRouteBuilderInterface
    {
        $this->setTabOrderToRoute($this->route, $tabOrder);

        return $this;
    }

    public function setTabCondition(string $tabCondition): ListRouteBuilderInterface
    {
        $this->setTabConditionToRoute($this->route, $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): ListRouteBuilderInterface
    {
        $this->addListAdaptersToRoute($this->route, $listAdapters);

        return $this;
    }

    public function addLocales(array $locales): ListRouteBuilderInterface
    {
        $this->addLocalesToRoute($this->route, $locales);

        return $this;
    }

    public function setDefaultLocale(string $locale): ListRouteBuilderInterface
    {
        $this->setDefaultLocaleToRoute($this->route, $locale);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): ListRouteBuilderInterface
    {
        $this->addToolbarActionsToRoute($this->route, $toolbarActions);

        return $this;
    }

    public function setAddRoute(string $addRoute): ListRouteBuilderInterface
    {
        $this->setAddRouteToRoute($this->route, $addRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): ListRouteBuilderInterface
    {
        $this->setEditRouteToRoute($this->route, $editRoute);

        return $this;
    }

    public function setBackRoute(string $backRoute): ListRouteBuilderInterface
    {
        $this->setBackRouteToRoute($this->route, $backRoute);

        return $this;
    }

    public function enableSearching(): ListRouteBuilderInterface
    {
        $this->setSearchableToRoute($this->route, true);

        return $this;
    }

    public function disableSearching(): ListRouteBuilderInterface
    {
        $this->setSearchableToRoute($this->route, false);

        return $this;
    }

    public function addRouterAttributesToListStore(array $routerAttributesToListStore): ListRouteBuilderInterface
    {
        $this->addRouterAttributesToListStoreToRoute($this->route, $routerAttributesToListStore);

        return $this;
    }

    public function addRouterAttributesToListMetadata(array $routerAttributesToListMetadata): ListRouteBuilderInterface
    {
        $this->addRouterAttributesToListMetadataToRoute($this->route, $routerAttributesToListMetadata);

        return $this;
    }

    public function addResourceStorePropertiesToListStore(array $resourceStorePropertiesToListStore): ListRouteBuilderInterface
    {
        $this->addResourceStorePropertiesToListStoreToRoute($this->route, $resourceStorePropertiesToListStore);

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
