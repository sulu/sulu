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

class PreviewFormViewBuilder implements PreviewFormViewBuilderInterface
{
    use ViewBuilderTrait;
    use FormViewBuilderTrait;
    use TabViewChildBuilderTrait;

    public const TYPE = 'sulu_admin.preview_form';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::TYPE);
    }

    public function disablePreviewWebspaceChooser(): PreviewFormViewBuilderInterface
    {
        $this->view->setOption('previewWebspaceChooser', false);

        return $this;
    }

    public function setResourceKey(string $resourceKey): PreviewFormViewBuilderInterface
    {
        $this->setResourceKeyToView($this->view, $resourceKey);

        return $this;
    }

    public function setPreviewResourceKey(string $previewResourceKey): PreviewFormViewBuilderInterface
    {
        $this->setOption('previewResourceKey', $previewResourceKey);

        return $this;
    }

    public function setFormKey(string $formKey): PreviewFormViewBuilderInterface
    {
        $this->setFormKeyToView($this->view, $formKey);

        return $this;
    }

    /**
     * @deprecated The usage of the "setRequestParameters" method in the PreviewFormViewBuilder is deprecated. Please use "addRequestParameters" instead.
     */
    public function setRequestParameters(array $requestParameters): PreviewFormViewBuilderInterface
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The usage of the "setRequestParameters" method in the PreviewFormViewBuilder is deprecated. Please use "addRequestParameters" instead.');

        $this->setRequestParametersToView($this->view, $requestParameters);

        return $this;
    }

    public function addLocales(array $locales): PreviewFormViewBuilderInterface
    {
        $this->addLocalesToView($this->view, $locales);

        return $this;
    }

    public function setTabTitle(string $tabTitle): PreviewFormViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabCondition(string $tabCondition): PreviewFormViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }

    public function setTabOrder(int $tabOrder): PreviewFormViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): PreviewFormViewBuilderInterface
    {
        $this->setTabPriorityToView($this->view, $tabPriority);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): PreviewFormViewBuilderInterface
    {
        $this->addToolbarActionsToView($this->view, $toolbarActions);

        return $this;
    }

    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): PreviewFormViewBuilderInterface
    {
        $this->addRouterAttributesToFormRequestToView($this->view, $routerAttributesToFormRequest);

        return $this;
    }

    public function addRouterAttributesToEditView(array $routerAttributesToEditView): PreviewFormViewBuilderInterface
    {
        $this->addRouterAttributesToEditViewToView($this->view, $routerAttributesToEditView);

        return $this;
    }

    public function addRouterAttributesToFormMetadata(
        array $routerAttributesToFormMetadata
    ): PreviewFormViewBuilderInterface {
        $this->addRouterAttributesToFormMetadataToView($this->view, $routerAttributesToFormMetadata);

        return $this;
    }

    public function addRequestParameters(array $requestParameters): PreviewFormViewBuilderInterface
    {
        $this->addRequestParametersToView($this->view, $requestParameters);

        return $this;
    }

    public function addMetadataRequestParameters(array $metadataRequestParameters): PreviewFormViewBuilderInterface
    {
        $this->addMetadataRequestParametersToView($this->view, $metadataRequestParameters);

        return $this;
    }

    public function setEditView(string $editView): PreviewFormViewBuilderInterface
    {
        $this->setEditViewToView($this->view, $editView);

        return $this;
    }

    public function setBackView(string $backView): PreviewFormViewBuilderInterface
    {
        $this->setBackViewToView($this->view, $backView);

        return $this;
    }

    public function setIdQueryParameter(string $idQueryParameter): PreviewFormViewBuilderInterface
    {
        $this->setIdQueryParameterToView($this->view, $idQueryParameter);

        return $this;
    }

    public function setPreviewCondition(string $previewCondition): PreviewFormViewBuilderInterface
    {
        $this->view->setOption('previewCondition', $previewCondition);

        return $this;
    }

    public function setTitleVisible(bool $titleVisible): PreviewFormViewBuilderInterface
    {
        $this->setTitleVisibleToView($this->view, $titleVisible);

        return $this;
    }

    public function addTabBadges(array $badges): PreviewFormViewBuilderInterface
    {
        $this->addTabBadgesToView($this->view, $badges);

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
