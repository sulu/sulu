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

    private function setApiOptionsToView(View $view, array $apiOptions): void
    {
        $view->setOption('apiOptions', $apiOptions);
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

    private function addLocalesToView(View $view, array $locales): void
    {
        $oldLocales = $view->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $view->setOption('locales', $newLocales);
    }

    private function addRouterAttributesToFormRequestToView(View $view, array $routerAttributesToFormRequest): void
    {
        $oldRouterAttributesToFormRequest = $view->getOption('routerAttributesToFormRequest');
        $newRouterAttributesToFormRequest = $oldRouterAttributesToFormRequest
            ? array_merge($oldRouterAttributesToFormRequest, $routerAttributesToFormRequest)
            : $routerAttributesToFormRequest;

        $view->setOption('routerAttributesToFormRequest', $newRouterAttributesToFormRequest);
    }

    private function addRouterAttributesToEditViewToView(View $view, array $routerAttributesToEditView): void
    {
        $oldRouterAttributesToEditView = $view->getOption('routerAttributesToEditView');
        $newRouterAttributesToEditView = $oldRouterAttributesToEditView
            ? array_merge($oldRouterAttributesToEditView, $routerAttributesToEditView)
            : $routerAttributesToEditView;

        $view->setOption('routerAttributesToEditView', $newRouterAttributesToEditView);
    }

    private function addRouterAttributesToBackViewToView(View $view, array $routerAttributesToBackView): void
    {
        $oldRouterAttributesToBackView = $view->getOption('routerAttributesToBackView');
        $newRouterAttributesToBackView = $oldRouterAttributesToBackView
            ? array_merge($oldRouterAttributesToBackView, $routerAttributesToBackView)
            : $routerAttributesToBackView;

        $view->setOption('routerAttributesToBackView', $newRouterAttributesToBackView);
    }
}
