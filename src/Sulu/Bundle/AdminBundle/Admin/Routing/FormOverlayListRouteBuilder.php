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

class FormOverlayListRouteBuilder implements FormOverlayListRouteBuilderInterface
{
    const VIEW = 'sulu_admin.form_overlay_list';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function setListKey(string $listKey): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('listKey', $listKey);

        return $this;
    }

    public function setFormKey(string $formKey): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('formKey', $formKey);

        return $this;
    }

    public function setTitle(string $title): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('title', $title);

        return $this;
    }

    public function setAddOverlayTitle(string $addOverlayTitle): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('addOverlayTitle', $addOverlayTitle);

        return $this;
    }

    public function setEditOverlayTitle(string $editOverlayTitle): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('editOverlayTitle', $editOverlayTitle);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('tabTitle', $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('tabOrder', $tabOrder);

        return $this;
    }

    public function setTabCondition(string $tabCondition): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('tabCondition', $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): FormOverlayListRouteBuilderInterface
    {
        $oldListAdapters = $this->route->getOption('adapters');
        $newListAdapters = $oldListAdapters ? array_merge($oldListAdapters, $listAdapters) : $listAdapters;
        $this->route->setOption('adapters', $newListAdapters);

        return $this;
    }

    public function addLocales(array $locales): FormOverlayListRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setDefaultLocale(string $locale): FormOverlayListRouteBuilderInterface
    {
        $this->route->setAttributeDefault('locale', $locale);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormOverlayListRouteBuilderInterface
    {
        $oldToolbarActions = $this->route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $this->route->setOption('toolbarActions', $newToolbarActions);

        return $this;
    }

    public function setBackRoute(string $backRoute): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('backRoute', $backRoute);

        return $this;
    }

    public function enableSearching(): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('searchable', true);

        return $this;
    }

    public function disableSearching(): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('searchable', false);

        return $this;
    }

    public function addRouterAttributesToListStore(array $routerAttributesToListStore): FormOverlayListRouteBuilderInterface
    {
        $oldRouterAttributesToListStore = $this->route->getOption('routerAttributesToListStore');
        $newRouterAttributesToListStore = $oldRouterAttributesToListStore ? array_merge($oldRouterAttributesToListStore, $routerAttributesToListStore) : $routerAttributesToListStore;
        $this->route->setOption('routerAttributesToListStore', $newRouterAttributesToListStore);

        return $this;
    }

    public function addRouterAttributesToFormStore(array $routerAttributesToFormStore): FormOverlayListRouteBuilderInterface
    {
        $oldRouterAttributesToFormStore = $this->route->getOption('routerAttributesToFormStore');
        $newRouterAttributesToFormStore = $oldRouterAttributesToFormStore ? array_merge($oldRouterAttributesToFormStore, $routerAttributesToFormStore) : $routerAttributesToFormStore;
        $this->route->setOption('routerAttributesToFormStore', $newRouterAttributesToFormStore);

        return $this;
    }

    public function setParent(string $parent): FormOverlayListRouteBuilderInterface
    {
        $this->route->setParent($parent);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a form-overlay-list view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->route->getOption('listKey')) {
            throw new \DomainException(
                'A route for a form-overlay-list view needs a "listKey" option.'
                . ' You have likely forgotten to call the "setListKey" method.'
            );
        }

        if (!$this->route->getOption('formKey')) {
            throw new \DomainException(
                'A route for a form-overlay-list view needs a "formKey" option.'
                . ' You have likely forgotten to call the "setFormKey" method.'
            );
        }

        if (!$this->route->getOption('adapters')) {
            throw new \DomainException(
                'A route for a form-overlay-list needs a "adapters" option.'
                . ' You have likely forgotten to call the "addListAdapters" method.'
            );
        }

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a form-overlay-list needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a form-overlay-list cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->route;
    }
}
