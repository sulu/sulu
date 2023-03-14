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

class FormViewBuilder implements FormViewBuilderInterface
{
    use ViewBuilderTrait;
    use FormViewBuilderTrait;
    use TabViewChildBuilderTrait;

    public const TYPE = 'sulu_admin.form';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::TYPE);
    }

    public function setResourceKey(string $resourceKey): FormViewBuilderInterface
    {
        $this->setResourceKeyToView($this->view, $resourceKey);

        return $this;
    }

    public function setFormKey(string $formKey): FormViewBuilderInterface
    {
        $this->setFormKeyToView($this->view, $formKey);

        return $this;
    }

    /**
     * @deprecated The usage of the "setRequestParameters" method in the FormViewBuilder is deprecated. Please use "addRequestParameters" instead.
     */
    public function setRequestParameters(array $requestParameters): FormViewBuilderInterface
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The usage of the "setRequestParameters" method in the FormViewBuilder is deprecated. Please use "addRequestParameters" instead.');

        $this->setRequestParametersToView($this->view, $requestParameters);

        return $this;
    }

    public function addLocales(array $locales): FormViewBuilderInterface
    {
        $this->addLocalesToView($this->view, $locales);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabCondition(string $tabCondition): FormViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }

    public function setTabOrder(int $tabOrder): FormViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): FormViewBuilderInterface
    {
        $this->setTabPriorityToView($this->view, $tabPriority);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormViewBuilderInterface
    {
        $this->addToolbarActionsToView($this->view, $toolbarActions);

        return $this;
    }

    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): FormViewBuilderInterface
    {
        $this->addRouterAttributesToFormRequestToView($this->view, $routerAttributesToFormRequest);

        return $this;
    }

    public function addRouterAttributesToEditView(array $routerAttributesToEditView): FormViewBuilderInterface
    {
        $this->addRouterAttributesToEditViewToView($this->view, $routerAttributesToEditView);

        return $this;
    }

    public function setEditView(string $editView): FormViewBuilderInterface
    {
        $this->setEditViewToView($this->view, $editView);

        return $this;
    }

    public function setBackView(string $backView): FormViewBuilderInterface
    {
        $this->setBackViewToView($this->view, $backView);

        return $this;
    }

    public function addRouterAttributesToBackView(
        array $routerAttributesToBackView
    ): FormViewBuilderInterface {
        $this->addRouterAttributesToBackViewToView($this->view, $routerAttributesToBackView);

        return $this;
    }

    public function addRouterAttributesToFormMetadata(array $routerAttributesToFormMetadata): FormViewBuilderInterface
    {
        $this->addRouterAttributesToFormMetadataToView($this->view, $routerAttributesToFormMetadata);

        return $this;
    }

    public function addMetadataRequestParameters(array $metadataRequestParameters): FormViewBuilderInterface
    {
        $this->addMetadataRequestParametersToView($this->view, $metadataRequestParameters);

        return $this;
    }

    public function addRequestParameters(array $requestParameters): FormViewBuilderInterface
    {
        $this->addRequestParametersToView($this->view, $requestParameters);

        return $this;
    }

    public function setIdQueryParameter(string $idQueryParameter): FormViewBuilderInterface
    {
        $this->setIdQueryParameterToView($this->view, $idQueryParameter);

        return $this;
    }

    public function setTitleVisible(bool $titleVisible): FormViewBuilderInterface
    {
        $this->setTitleVisibleToView($this->view, $titleVisible);

        return $this;
    }

    public function addTabBadges(array $badges): FormViewBuilderInterface
    {
        $this->addTabBadgesToView($this->view, $badges);

        return $this;
    }

    public function enableTabGap(): FormViewBuilderInterface
    {
        $this->setDisableTabGapToView($this->view, false);

        return $this;
    }

    public function disableTabGap(): FormViewBuilderInterface
    {
        $this->setDisableTabGapToView($this->view, true);

        return $this;
    }

    public function getView(): View
    {
        if (!$this->view->getOption('resourceKey')) {
            throw new \DomainException(
                'A view for a Form view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->view->getOption('formKey')) {
            throw new \DomainException(
                'A view for a Form view needs a "formKey" option.'
                . ' You have likely forgotten to call the "setFormKey" method.'
            );
        }

        if ($this->view->getOption('locales') && false === \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a Form view needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->view->getOption('locales') && false !== \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a Form view cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        if ($this->view->getOption('editView') === $this->view->getName()) {
            throw new \DomainException(
                'A view for a Form view should not redirect to itself using the "editView" option.'
            );
        }

        return clone $this->view;
    }
}
