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

class FormOverlayDatagridRouteBuilder implements FormOverlayDatagridRouteBuilderInterface
{
    const VIEW = 'sulu_admin.form_overlay_datagrid';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function setDatagridkey(string $datagridKey): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('datagridKey', $datagridKey);

        return $this;
    }

    public function setTitle(string $title): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('title', $title);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('tabTitle', $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('tabOrder', $tabOrder);

        return $this;
    }

    public function setTabCondition(string $tabCondition): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('tabCondition', $tabCondition);

        return $this;
    }

    public function addDatagridAdapters(array $datagridAdapters): FormOverlayDatagridRouteBuilderInterface
    {
        $oldDatagridAdapters = $this->route->getOption('adapters');
        $newDatagridAdapters = $oldDatagridAdapters ? array_merge($oldDatagridAdapters, $datagridAdapters) : $datagridAdapters;
        $this->route->setOption('adapters', $newDatagridAdapters);

        return $this;
    }

    public function addLocales(array $locales): FormOverlayDatagridRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setDefaultLocale(string $locale): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setAttributeDefault('locale', $locale);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormOverlayDatagridRouteBuilderInterface
    {
        $oldToolbarActions = $this->route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $this->route->setOption('toolbarActions', $newToolbarActions);

        return $this;
    }

    public function setAddFormKey(string $addFormKey): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('addFormKey', $addFormKey);

        return $this;
    }

    public function setEditFormKey(string $editFormKey): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('editFormKey', $editFormKey);

        return $this;
    }

    public function setBackRoute(string $backRoute): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('backRoute', $backRoute);

        return $this;
    }

    public function enableSearching(): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('searchable', true);

        return $this;
    }

    public function disableSearching(): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('searchable', false);

        return $this;
    }

    public function enableMoving(): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('movable', true);

        return $this;
    }

    public function disableMoving(): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setOption('movable', false);

        return $this;
    }

    public function addRouterAttributesToDatagridStore(array $routerAttributesToDatagridStore): FormOverlayDatagridRouteBuilderInterface
    {
        $oldRouterAttributesToDatagridStore = $this->route->getOption('routerAttributesToDatagridStore');
        $newRouterAttributesToDatagridStore = $oldRouterAttributesToDatagridStore ? array_merge($oldRouterAttributesToDatagridStore, $routerAttributesToDatagridStore) : $routerAttributesToDatagridStore;
        $this->route->setOption('routerAttributesToDatagridStore', $newRouterAttributesToDatagridStore);

        return $this;
    }

    public function setParent(string $parent): FormOverlayDatagridRouteBuilderInterface
    {
        $this->route->setParent($parent);

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

        if (!$this->route->getOption('datagridKey')) {
            throw new \DomainException(
                'A route for a datagrid view needs a "datagridKey" option.'
                . ' You have likely forgotten to call the "setDatagridKey" method.'
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

        return clone $this->route;
    }
}
