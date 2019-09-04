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

class PreviewFormRouteBuilder implements PreviewFormRouteBuilderInterface
{
    use RouteBuilderTrait;
    use FormRouteBuilderTrait;
    use TabRouteBuilderTrait;

    const VIEW = 'sulu_admin.preview_form';

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): PreviewFormRouteBuilderInterface
    {
        $this->setResourceKeyToRoute($this->route, $resourceKey);

        return $this;
    }

    public function setFormKey(string $formKey): PreviewFormRouteBuilderInterface
    {
        $this->setFormKeyToRoute($this->route, $formKey);

        return $this;
    }

    public function setApiOptions(array $apiOptions): PreviewFormRouteBuilderInterface
    {
        $this->setApiOptionsToRoute($this->route, $apiOptions);

        return $this;
    }

    public function addLocales(array $locales): PreviewFormRouteBuilderInterface
    {
        $this->addLocalesToRoute($this->route, $locales);

        return $this;
    }

    public function setTabTitle(string $tabTitle): PreviewFormRouteBuilderInterface
    {
        $this->setTabTitleToRoute($this->route, $tabTitle);

        return $this;
    }

    public function setTabCondition(string $tabCondition): PreviewFormRouteBuilderInterface
    {
        $this->setTabConditionToRoute($this->route, $tabCondition);

        return $this;
    }

    public function setTabOrder(int $tabOrder): PreviewFormRouteBuilderInterface
    {
        $this->setTabOrderToRoute($this->route, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): PreviewFormRouteBuilderInterface
    {
        $this->setTabPriorityToRoute($this->route, $tabPriority);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): PreviewFormRouteBuilderInterface
    {
        $this->addToolbarActionsToRoute($this->route, $toolbarActions);

        return $this;
    }

    public function addRouterAttributesToFormStore(array $routerAttributesToFormStore): PreviewFormRouteBuilderInterface
    {
        $this->addRouterAttributesToFormStoreToRoute($this->route, $routerAttributesToFormStore);

        return $this;
    }

    public function addRouterAttributesToEditRoute(array $routerAttributesToEditRoute): PreviewFormRouteBuilderInterface
    {
        $this->addRouterAttributesToEditRouteToRoute($this->route, $routerAttributesToEditRoute);

        return $this;
    }

    public function setEditRoute(string $editRoute): PreviewFormRouteBuilderInterface
    {
        $this->setEditRouteToRoute($this->route, $editRoute);

        return $this;
    }

    public function setBackRoute(string $backRoute): PreviewFormRouteBuilderInterface
    {
        $this->setBackRouteToRoute($this->route, $backRoute);

        return $this;
    }

    public function setIdQueryParameter(string $idQueryParameter): PreviewFormRouteBuilderInterface
    {
        $this->setIdQueryParameterToRoute($this->route, $idQueryParameter);

        return $this;
    }

    public function setPreviewCondition(string $previewCondition): PreviewFormRouteBuilderInterface
    {
        $this->route->setOption('previewCondition', $previewCondition);

        return $this;
    }

    public function setTitleVisible(bool $titleVisible): PreviewFormRouteBuilderInterface
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

        return clone $this->route;
    }
}
