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

class FormRouteBuilder implements FormRouteBuilderInterface
{
    use RouteBuilderTrait;
    use FormRouteBuilderTrait;
    use TabRouteBuilderTrait;

    const VIEW = 'sulu_admin.form';

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): FormRouteBuilderInterface
    {
        $this->setResourceKeyToRoute($this->route, $resourceKey);

        return $this;
    }

    public function setFormKey(string $formKey): FormRouteBuilderInterface
    {
        $this->setFormKeyToRoute($this->route, $formKey);

        return $this;
    }

    public function setApiOptions(array $apiOptions): FormRouteBuilderInterface
    {
        $this->setApiOptionsToRoute($this->route, $apiOptions);

        return $this;
    }

    public function addLocales(array $locales): FormRouteBuilderInterface
    {
        $this->addLocalesToRoute($this->route, $locales);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormRouteBuilderInterface
    {
        $this->setTabTitleToRoute($this->route, $tabTitle);

        return $this;
    }

    public function setTabCondition(string $tabCondition): FormRouteBuilderInterface
    {
        $this->setTabConditionToRoute($this->route, $tabCondition);

        return $this;
    }

    public function setTabOrder(int $tabOrder): FormRouteBuilderInterface
    {
        $this->setTabOrderToRoute($this->route, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): FormRouteBuilderInterface
    {
        $this->setTabPriorityToRoute($this->route, $tabPriority);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormRouteBuilderInterface
    {
        $this->addToolbarActionsToRoute($this->route, $toolbarActions);

        return $this;
    }

    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): FormRouteBuilderInterface
    {
        $this->addRouterAttributesToFormRequestToRoute($this->route, $routerAttributesToFormRequest);

        return $this;
    }

    public function addRouterAttributesToEditRoute(array $routerAttributesToEditRoute): FormRouteBuilderInterface
    {
        $this->addRouterAttributesToEditRouteToRoute($this->route, $routerAttributesToEditRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): FormRouteBuilderInterface
    {
        $this->setEditRouteToRoute($this->route, $editRoute);

        return $this;
    }

    public function setBackRoute(string $backRoute): FormRouteBuilderInterface
    {
        $this->setBackRouteToRoute($this->route, $backRoute);

        return $this;
    }

    public function addRouterAttributesToBackRoute(
        array $routerAttributesToBackRoute
    ): FormRouteBuilderInterface {
        $this->addRouterAttributesToBackRouteToRoute($this->route, $routerAttributesToBackRoute);

        return $this;
    }

    public function setIdQueryParameter(string $idQueryParameter): FormRouteBuilderInterface
    {
        $this->setIdQueryParameterToRoute($this->route, $idQueryParameter);

        return $this;
    }

    public function setTitleVisible(bool $titleVisible): FormRouteBuilderInterface
    {
        $this->setTitleVisibleToRoute($this->route, $titleVisible);

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

        if ($this->route->getOption('editRoute') === $this->route->getName()) {
            throw new \DomainException(
                'A route for a Form view should not redirect to itself using the "editRoute" option.'
            );
        }

        return clone $this->route;
    }
}
