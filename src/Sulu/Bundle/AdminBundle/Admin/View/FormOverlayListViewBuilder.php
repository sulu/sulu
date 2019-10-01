<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

class FormOverlayListViewBuilder implements FormOverlayListViewBuilderInterface
{
    use ViewBuilderTrait;
    use ListViewBuilderTrait, FormViewBuilderTrait {
        ListViewBuilderTrait::setResourceKeyToView insteadof FormViewBuilderTrait;
        ListViewBuilderTrait::setBackViewToView insteadof FormViewBuilderTrait;
        ListViewBuilderTrait::setEditViewToView insteadof FormViewBuilderTrait;
        ListViewBuilderTrait::addLocalesToView insteadof FormViewBuilderTrait;
        ListViewBuilderTrait::addToolbarActionsToView insteadof FormViewBuilderTrait;
    }
    use TabViewBuilderTrait;

    const VIEW = 'sulu_admin.form_overlay_list';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): FormOverlayListViewBuilderInterface
    {
        $this->setResourceKeyToView($this->view, $resourceKey);

        return $this;
    }

    public function setListKey(string $listKey): FormOverlayListViewBuilderInterface
    {
        $this->setListKeyToView($this->view, $listKey);

        return $this;
    }

    public function setFormKey(string $formKey): FormOverlayListViewBuilderInterface
    {
        $this->setFormKeyToView($this->view, $formKey);

        return $this;
    }

    public function setApiOptions(array $apiOptions): FormOverlayListViewBuilderInterface
    {
        $this->setApiOptionsToView($this->view, $apiOptions);

        return $this;
    }

    public function setTitle(string $title): FormOverlayListViewBuilderInterface
    {
        $this->setTitleToView($this->view, $title);

        return $this;
    }

    public function setAddOverlayTitle(string $addOverlayTitle): FormOverlayListViewBuilderInterface
    {
        $this->view->setOption('addOverlayTitle', $addOverlayTitle);

        return $this;
    }

    public function setEditOverlayTitle(string $editOverlayTitle): FormOverlayListViewBuilderInterface
    {
        $this->view->setOption('editOverlayTitle', $editOverlayTitle);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormOverlayListViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): FormOverlayListViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabCondition(string $tabCondition): FormOverlayListViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): FormOverlayListViewBuilderInterface
    {
        $this->addListAdaptersToView($this->view, $listAdapters);

        return $this;
    }

    public function addLocales(array $locales): FormOverlayListViewBuilderInterface
    {
        $this->addLocalesToView($this->view, $locales);

        return $this;
    }

    public function setDefaultLocale(string $locale): FormOverlayListViewBuilderInterface
    {
        $this->setDefaultLocaleToView($this->view, $locale);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormOverlayListViewBuilderInterface
    {
        $this->addToolbarActionsToView($this->view, $toolbarActions);

        return $this;
    }

    public function setBackView(string $backView): FormOverlayListViewBuilderInterface
    {
        $this->setBackViewToView($this->view, $backView);

        return $this;
    }

    public function enableSearching(): FormOverlayListViewBuilderInterface
    {
        $this->setSearchableToView($this->view, true);

        return $this;
    }

    public function disableSearching(): FormOverlayListViewBuilderInterface
    {
        $this->setSearchableToView($this->view, false);

        return $this;
    }

    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): FormOverlayListViewBuilderInterface
    {
        $this->addRouterAttributesToListRequestToView($this->view, $routerAttributesToListRequest);

        return $this;
    }

    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): FormOverlayListViewBuilderInterface
    {
        $this->addRouterAttributesToFormRequestToView($this->view, $routerAttributesToFormRequest);

        return $this;
    }

    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): FormOverlayListViewBuilderInterface
    {
        $this->addResourceStorePropertiesToListRequestToView($this->view, $resourceStorePropertiesToListRequest);

        return $this;
    }

    public function addResourceStorePropertiesToFormRequest(array $resourceStorePropertiesToFormRequest): FormOverlayListViewBuilderInterface
    {
        $oldResourceStorePropertiesToFormRequest = $this->view->getOption('resourceStorePropertiesToFormRequest');
        $newResourceStorePropertiesToFormRequest = $oldResourceStorePropertiesToFormRequest ? array_merge($oldResourceStorePropertiesToFormRequest, $resourceStorePropertiesToFormRequest) : $resourceStorePropertiesToFormRequest;
        $this->view->setOption('resourceStorePropertiesToFormRequest', $newResourceStorePropertiesToFormRequest);

        return $this;
    }

    public function setOverlaySize(string $overlaySize): FormOverlayListViewBuilderInterface
    {
        $this->view->setOption('overlaySize', $overlaySize);

        return $this;
    }

    public function getView(): View
    {
        if (!$this->view->getOption('resourceKey')) {
            throw new \DomainException(
                'A view for a form-overlay-list view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->view->getOption('listKey')) {
            throw new \DomainException(
                'A view for a form-overlay-list view needs a "listKey" option.'
                . ' You have likely forgotten to call the "setListKey" method.'
            );
        }

        if (!$this->view->getOption('formKey')) {
            throw new \DomainException(
                'A view for a form-overlay-list view needs a "formKey" option.'
                . ' You have likely forgotten to call the "setFormKey" method.'
            );
        }

        if (!$this->view->getOption('adapters')) {
            throw new \DomainException(
                'A view for a form-overlay-list needs a "adapters" option.'
                . ' You have likely forgotten to call the "addListAdapters" method.'
            );
        }

        if ($this->view->getOption('locales') && false === strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a form-overlay-list needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->view->getOption('locales') && false !== strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a form-overlay-list cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->view;
    }
}
