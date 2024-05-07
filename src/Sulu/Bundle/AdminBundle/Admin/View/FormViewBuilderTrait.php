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

trait FormViewBuilderTrait
{
    use ToolbarActionsViewBuilderTrait;

    private function setResourceKeyToView(View $view, string $resourceKey): void
    {
        $view->setOption('resourceKey', $resourceKey);
    }

    private function setFormKeyToView(View $view, string $formKey): void
    {
        $view->setOption('formKey', $formKey);
    }

    private function setBackViewToView(View $view, string $backView): void
    {
        $view->setOption('backView', $backView);
    }

    private function setEditViewToView(View $view, string $editView): void
    {
        $view->setOption('editView', $editView);
    }

    private function setIdQueryParameterToView(View $view, string $idQueryParameter): void
    {
        $view->setOption('idQueryParameter', $idQueryParameter);
    }

    private function setTitleVisibleToView(View $view, bool $titleVisible): void
    {
        $view->setOption('titleVisible', $titleVisible);
    }

    /**
     * @param string[] $locales
     */
    private function addLocalesToView(View $view, array $locales): void
    {
        $oldLocales = $view->getOption('locales');
        $newLocales = $oldLocales ? \array_merge($oldLocales, $locales) : $locales;
        $view->setOption('locales', $newLocales);

        if (!$view->getAttributeDefault('locale') && isset($newLocales[0])) {
            $view->setAttributeDefault('locale', $newLocales[0]);
        }
    }

    /**
     * @param array<string|int, mixed> $routerAttributesToFormRequest
     */
    private function addRouterAttributesToFormRequestToView(View $view, array $routerAttributesToFormRequest): void
    {
        $oldRouterAttributesToFormRequest = $view->getOption('routerAttributesToFormRequest');
        $newRouterAttributesToFormRequest = $oldRouterAttributesToFormRequest
            ? \array_merge($oldRouterAttributesToFormRequest, $routerAttributesToFormRequest)
            : $routerAttributesToFormRequest;

        $view->setOption('routerAttributesToFormRequest', $newRouterAttributesToFormRequest);
    }

    /**
     * @param array<string, mixed> $routerAttributesToEditView
     */
    private function addRouterAttributesToEditViewToView(View $view, array $routerAttributesToEditView): void
    {
        $oldRouterAttributesToEditView = $view->getOption('routerAttributesToEditView');
        $newRouterAttributesToEditView = $oldRouterAttributesToEditView
            ? \array_merge($oldRouterAttributesToEditView, $routerAttributesToEditView)
            : $routerAttributesToEditView;

        $view->setOption('routerAttributesToEditView', $newRouterAttributesToEditView);
    }

    /**
     * @param array<string, mixed> $routerAttributesToBackView
     */
    private function addRouterAttributesToBackViewToView(View $view, array $routerAttributesToBackView): void
    {
        $oldRouterAttributesToBackView = $view->getOption('routerAttributesToBackView');
        $newRouterAttributesToBackView = $oldRouterAttributesToBackView
            ? \array_merge($oldRouterAttributesToBackView, $routerAttributesToBackView)
            : $routerAttributesToBackView;

        $view->setOption('routerAttributesToBackView', $newRouterAttributesToBackView);
    }

    /**
     * @param array<string|int, string> $routerAttributesToFormMetadata
     */
    private function addRouterAttributesToFormMetadataToView(View $route, array $routerAttributesToFormMetadata): void
    {
        $oldRouterAttributesToFormMetadata = $route->getOption('routerAttributesToFormMetadata');
        $newRouterAttributesToFormMetadata = $oldRouterAttributesToFormMetadata
            ? \array_merge($oldRouterAttributesToFormMetadata, $routerAttributesToFormMetadata)
            : $routerAttributesToFormMetadata;

        $route->setOption('routerAttributesToFormMetadata', $newRouterAttributesToFormMetadata);
    }

    /**
     * @param array<string, mixed> $metadataRequestParameters
     */
    private function addMetadataRequestParametersToView(View $route, array $metadataRequestParameters): void
    {
        $oldMetadataRequestParameters = $route->getOption('metadataRequestParameters');
        $newMetadataRequestParameters = $oldMetadataRequestParameters ? \array_merge($oldMetadataRequestParameters, $metadataRequestParameters) : $metadataRequestParameters;

        $route->setOption('metadataRequestParameters', $newMetadataRequestParameters);
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    private function addRequestParametersToView(View $route, array $requestParameters): void
    {
        $oldRequestParameters = $route->getOption('requestParameters');
        $newRequestParameters = $oldRequestParameters ? \array_merge($oldRequestParameters, $requestParameters) : $requestParameters;

        $route->setOption('requestParameters', $newRequestParameters);
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    private function setRequestParametersToView(View $view, array $requestParameters): void
    {
        $view->setOption('requestParameters', $requestParameters);
    }
}
