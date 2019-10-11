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

trait ListViewBuilderTrait
{
    use ToolbarActionsViewBuilderTrait;

    private function setResourceKeyToView(View $route, string $resourceKey): void
    {
        $route->setOption('resourceKey', $resourceKey);
    }

    private function setListKeyToView(View $route, string $listKey): void
    {
        $route->setOption('listKey', $listKey);
    }

    private function setUserSettingsKeyToView(View $route, string $userSettingsKey): void
    {
        $route->setOption('userSettingsKey', $userSettingsKey);
    }

    private function setTitleToView(View $route, string $title): void
    {
        $route->setOption('title', $title);
    }

    private function addListAdaptersToView(View $route, array $listAdapters): void
    {
        $oldListAdapters = $route->getOption('adapters');
        $newListAdapters = $oldListAdapters ? array_merge($oldListAdapters, $listAdapters) : $listAdapters;
        $route->setOption('adapters', $newListAdapters);
    }

    private function setBackViewToView(View $route, string $backView): void
    {
        $route->setOption('backView', $backView);
    }

    private function setAddViewToView(View $route, string $addView): void
    {
        $route->setOption('addView', $addView);
    }

    private function setEditViewToView(View $route, string $editView): void
    {
        $route->setOption('editView', $editView);
    }

    private function setSearchableToView(View $route, bool $searchable): void
    {
        $route->setOption('searchable', $searchable);
    }

    private function addRouterAttributesToListRequestToView(View $route, array $routerAttributesToListRequest): void
    {
        $oldRouterAttributesToListRequest = $route->getOption('routerAttributesToListRequest');
        $newRouterAttributesToListRequest = $oldRouterAttributesToListRequest
            ? array_merge($oldRouterAttributesToListRequest, $routerAttributesToListRequest)
            : $routerAttributesToListRequest;

        $route->setOption('routerAttributesToListRequest', $newRouterAttributesToListRequest);
    }

    private function addRouterAttributesToListMetadataToView(View $route, array $routerAttributesToListMetadata): void
    {
        $oldRouterAttributesToListMetadata = $route->getOption('routerAttributesToListMetadata');
        $newRouterAttributesToListMetadata = $oldRouterAttributesToListMetadata
            ? array_merge($oldRouterAttributesToListMetadata, $routerAttributesToListMetadata)
            : $routerAttributesToListMetadata;

        $route->setOption('routerAttributesToListMetadata', $newRouterAttributesToListMetadata);
    }

    private function addFormMetadataToView(View $route, array $routerAttributesToFormMetadata):void
    {
        $oldRouterAttributesToFormMetadata = $route->getOption('formMetadata');
        $newRouterAttributesToFormMetadata = $oldRouterAttributesToFormMetadata
            ? array_merge($oldRouterAttributesToFormMetadata, $routerAttributesToFormMetadata)
            : $routerAttributesToFormMetadata;

        $route->setOption('formMetadata', $newRouterAttributesToFormMetadata);
    }

    private function addLocalesToView(View $route, array $locales): void
    {
        $oldLocales = $route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $route->setOption('locales', $newLocales);
    }

    private function setDefaultLocaleToView(View $route, string $locale): void
    {
        $route->setAttributeDefault('locale', $locale);
    }

    private function addResourceStorePropertiesToListRequestToView(View $route, array $resourceStorePropertiesToListRequest): void
    {
        $oldResourceStorePropertiesToListRequest = $route->getOption('resourceStorePropertiesToListRequest');
        $newResourceStorePropertiesToListRequest = $oldResourceStorePropertiesToListRequest
            ? array_merge($oldResourceStorePropertiesToListRequest, $resourceStorePropertiesToListRequest)
            : $resourceStorePropertiesToListRequest;

        $route->setOption('resourceStorePropertiesToListRequest', $newResourceStorePropertiesToListRequest);
    }
}
