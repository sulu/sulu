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

class FormRouteBuilder implements FormRouteBuilderInterface
{
    const VIEW = 'sulu_admin.form';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): FormRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function setFormKey(string $formKey): FormRouteBuilderInterface
    {
        $this->route->addOption('formKey', $formKey);

        return $this;
    }

    public function addLocales(array $locales): FormRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormRouteBuilderInterface
    {
        $this->route->setOption('tabTitle', $tabTitle);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormRouteBuilderInterface
    {
        $oldToolbarActions = $this->route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $this->route->setOption('toolbarActions', $newToolbarActions);

        return $this;
    }

    public function addRouterAttributesToFormStore(array $routerAttributesToFormStore): FormRouteBuilderInterface
    {
        $oldRouterAttributesToFormStore = $this->route->getOption('routerAttributesToFormStore');
        $newRouterAttributesToFormStore = $oldRouterAttributesToFormStore ? array_merge($oldRouterAttributesToFormStore, $routerAttributesToFormStore) : $routerAttributesToFormStore;
        $this->route->setOption('routerAttributesToFormStore', $newRouterAttributesToFormStore);

        return $this;
    }

    public function addRouterAttributesToEditRoute(array $routerAttributesToEditRoute): FormRouteBuilderInterface
    {
        $oldRouterAttributesToEditRoute = $this->route->getOption('routerAttributesToEditRoute');
        $newRouterAttributesToEditRoute = $oldRouterAttributesToEditRoute ? array_merge($oldRouterAttributesToEditRoute, $routerAttributesToEditRoute) : $routerAttributesToEditRoute;
        $this->route->setOption('routerAttributesToEditRoute', $newRouterAttributesToEditRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): FormRouteBuilderInterface
    {
        $this->route->setOption('editRoute', $editRoute);

        return $this;
    }

    public function setBackRoute(string $backRoute): FormRouteBuilderInterface
    {
        $this->route->setOption('backRoute', $backRoute);

        return $this;
    }

    public function setIdQueryParameter(string $idQueryParameter): FormRouteBuilderInterface
    {
        $this->route->setOption('idQueryParameter', $idQueryParameter);

        return $this;
    }

    public function setPreviewCondition(string $previewCondition): FormRouteBuilderInterface
    {
        $this->route->setOption('preview', $previewCondition);

        return $this;
    }

    public function setParent(string $parent): FormRouteBuilderInterface
    {
        $this->route->setParent($parent);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a Form view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->route->getOption('formKey')) {
            throw new \DomainException(
                'A route for a Form view needs a "formKey" option.'
                . ' You have likely forgotten to call the "setFormKey" method.'
            );
        }

        return $this->route;
    }
}
