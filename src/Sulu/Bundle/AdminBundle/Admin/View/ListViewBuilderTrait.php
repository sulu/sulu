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

    /**
     * @param string[] $listAdapters
     */
    private function addListAdaptersToView(View $route, array $listAdapters): void
    {
        /**
         * @deprecated this is only a BC-layer, should be removed in 3.0
         */
        $adapterModifiers = [
            '_light' => [
                'skin' => 'light',
            ],
            '_slim' => [
                'show_header' => false,
            ],
        ];

        foreach ($listAdapters as $index => $adapter) {
            foreach ($adapterModifiers as $key => $options) {
                if (0 === \substr_compare($adapter, $key, -\strlen($key))) {
                    $defaultAdapter = \str_replace($key, '', $adapter);
                    $this->addAdapterOptionsToView($route, [$defaultAdapter => $options]);
                    $listAdapters[$index] = $defaultAdapter;

                    @trigger_deprecation('sulu/sulu', '2.3',
                        'The usage of the "' . $adapter . '" is deprecated.' .
                        'Please use "' . $defaultAdapter . '"  with adapterOptions instead.'
                    );
                }
            }
        }

        $oldListAdapters = $route->getOption('adapters');
        $newListAdapters = $oldListAdapters ? \array_merge($oldListAdapters, $listAdapters) : $listAdapters;
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

    private function setSelectableToView(View $route, bool $selectable): void
    {
        $route->setOption('selectable', $selectable);
    }

    private function setPaginatedToView(View $route, bool $paginated): void
    {
        $route->setOption('paginated', $paginated);
    }

    private function setHideColumnOptionsToView(View $route, bool $hideColumnOptions): void
    {
        $route->setOption('hideColumnOptions', $hideColumnOptions);
    }

    private function setFilterableToView(View $route, bool $filterable): void
    {
        $route->setOption('filterable', $filterable);
    }

    /**
     * @param array<string, array<string, mixed>> $adapterOptions
     */
    private function addAdapterOptionsToView(View $route, array $adapterOptions): void
    {
        $oldAdapterOptions = $route->getOption('adapterOptions');
        $newAdapterOptions = $oldAdapterOptions ? \array_merge_recursive($oldAdapterOptions, $adapterOptions) : $adapterOptions;
        $route->setOption('adapterOptions', $newAdapterOptions);
    }

    private function addRouterAttributesToListRequestToView(View $route, array $routerAttributesToListRequest): void
    {
        $oldRouterAttributesToListRequest = $route->getOption('routerAttributesToListRequest');
        $newRouterAttributesToListRequest = $oldRouterAttributesToListRequest
            ? \array_merge($oldRouterAttributesToListRequest, $routerAttributesToListRequest)
            : $routerAttributesToListRequest;

        $route->setOption('routerAttributesToListRequest', $newRouterAttributesToListRequest);
    }

    private function addRouterAttributesToListMetadataToView(View $route, array $routerAttributesToListMetadata): void
    {
        $oldRouterAttributesToListMetadata = $route->getOption('routerAttributesToListMetadata');
        $newRouterAttributesToListMetadata = $oldRouterAttributesToListMetadata
            ? \array_merge($oldRouterAttributesToListMetadata, $routerAttributesToListMetadata)
            : $routerAttributesToListMetadata;

        $route->setOption('routerAttributesToListMetadata', $newRouterAttributesToListMetadata);
    }

    /**
     * @param string[] $locales
     */
    private function addLocalesToView(View $route, array $locales): void
    {
        $oldLocales = $route->getOption('locales');
        $newLocales = $oldLocales ? \array_merge($oldLocales, $locales) : $locales;
        $route->setOption('locales', $newLocales);

        if (!$route->getAttributeDefault('locale') && isset($newLocales[0])) {
            $this->setDefaultLocaleToView($route, $newLocales[0]);
        }
    }

    private function setDefaultLocaleToView(View $route, string $locale): void
    {
        $route->setAttributeDefault('locale', $locale);
    }

    private function setItemDisabledConditionToView(View $route, string $itemDisabledCondition): void
    {
        $route->setOption('itemDisabledCondition', $itemDisabledCondition);
    }

    private function addResourceStorePropertiesToListRequestToView(View $route, array $resourceStorePropertiesToListRequest): void
    {
        $oldResourceStorePropertiesToListRequest = $route->getOption('resourceStorePropertiesToListRequest');
        $newResourceStorePropertiesToListRequest = $oldResourceStorePropertiesToListRequest
            ? \array_merge($oldResourceStorePropertiesToListRequest, $resourceStorePropertiesToListRequest)
            : $resourceStorePropertiesToListRequest;

        $route->setOption('resourceStorePropertiesToListRequest', $newResourceStorePropertiesToListRequest);
    }

    private function addResourceStorePropertiesToListMetadataToView(View $route, array $resourceStorePropertiesToListMetadata): void
    {
        $oldResourceStorePropertiesToListMetadata = $route->getOption('resourceStorePropertiesToListMetadata');
        $newResourceStorePropertiesToListMetadata = $oldResourceStorePropertiesToListMetadata
            ? \array_merge($oldResourceStorePropertiesToListMetadata, $resourceStorePropertiesToListMetadata)
            : $resourceStorePropertiesToListMetadata;

        $route->setOption('resourceStorePropertiesToListMetadata', $newResourceStorePropertiesToListMetadata);
    }

    /**
     * @param mixed[] $metadataRequestParameters
     */
    private function addMetadataRequestParametersToView(View $route, array $metadataRequestParameters): void
    {
        $oldMetadataRequestParameters = $route->getOption('metadataRequestParameters');
        $newMetadataRequestParameters = $oldMetadataRequestParameters ? \array_merge($oldMetadataRequestParameters, $metadataRequestParameters) : $metadataRequestParameters;

        $route->setOption('metadataRequestParameters', $newMetadataRequestParameters);
    }

    private function addRequestParametersToView(View $route, array $requestParameters): void
    {
        $oldRequestParameters = $route->getOption('requestParameters');
        $newRequestParameters = $oldRequestParameters ? \array_merge($oldRequestParameters, $requestParameters) : $requestParameters;

        $route->setOption('requestParameters', $newRequestParameters);
    }

    /**
     * @param ListItemAction[] $itemActions
     */
    private function addItemActionsToView(View $view, array $itemActions): void
    {
        $oldItemActions = $view->getOption('itemActions');
        $newItemActions = $oldItemActions
            ? \array_merge($oldItemActions, $itemActions)
            : $itemActions;
        $view->setOption('itemActions', $newItemActions);
    }
}
