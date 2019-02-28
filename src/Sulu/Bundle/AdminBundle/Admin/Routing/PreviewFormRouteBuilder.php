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

class PreviewFormRouteBuilder implements PreviewFormRouteBuilderInterface
{
    const VIEW = 'sulu_admin.preview_form';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function setFormKey(string $formKey): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('formKey', $formKey);

        return $this;
    }

    public function addLocales(array $locales): PreviewFormRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->setOption('locales', $newLocales);

        return $this;
    }

    public function setTabTitle(string $tabTitle): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('tabTitle', $tabTitle);

        return $this;
    }

    public function setTabCondition(string $tabCondition): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('tabCondition', $tabCondition);

        return $this;
    }

    public function setTabOrder(int $tabOrder): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('tabOrder', $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('tabPriority', $tabPriority);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): PreviewFormRouteBuilderInterface
    {
        $oldToolbarActions = $this->route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $this->route->setOption('toolbarActions', $newToolbarActions);

        return $this;
    }

    public function addRouterAttributesToFormStore(array $routerAttributesToFormStore): PreviewFormRouteBuilderInterface
    {
        $oldRouterAttributesToFormStore = $this->route->getOption('routerAttributesToFormStore');
        $newRouterAttributesToFormStore = $oldRouterAttributesToFormStore ? array_merge($oldRouterAttributesToFormStore, $routerAttributesToFormStore) : $routerAttributesToFormStore;
        $this->route->setOption('routerAttributesToFormStore', $newRouterAttributesToFormStore);

        return $this;
    }

    public function addRouterAttributesToEditRoute(array $routerAttributesToEditRoute): PreviewFormRouteBuilderInterface
    {
        $oldRouterAttributesToEditRoute = $this->route->getOption('routerAttributesToEditRoute');
        $newRouterAttributesToEditRoute = $oldRouterAttributesToEditRoute ? array_merge($oldRouterAttributesToEditRoute, $routerAttributesToEditRoute) : $routerAttributesToEditRoute;
        $this->route->setOption('routerAttributesToEditRoute', $newRouterAttributesToEditRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('editRoute', $editRoute);

        return $this;
    }

    public function setBackRoute(string $backRoute): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('backRoute', $backRoute);

        return $this;
    }

    public function setIdQueryParameter(string $idQueryParameter): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('idQueryParameter', $idQueryParameter);

        return $this;
    }

    public function setPreviewCondition(string $previewCondition): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('previewCondition', $previewCondition);

        return $this;
    }

    public function setParent(string $parent): PreviewFormRouteBuilderInterface
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

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a Form view needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a Form view cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->route;
    }
}
