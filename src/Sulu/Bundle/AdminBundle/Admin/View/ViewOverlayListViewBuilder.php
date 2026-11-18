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

/**
 * @experimental
 */
class ViewOverlayListViewBuilder implements ViewOverlayListViewBuilderInterface
{
    use ViewBuilderTrait;
    use ListViewBuilderTrait;
    use TabViewChildBuilderTrait;

    public const TYPE = 'sulu_admin.view_overlay_list';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, self::TYPE);
    }

    public function setResourceKey(string $resourceKey): ViewOverlayListViewBuilderInterface
    {
        $this->setResourceKeyToView($this->view, $resourceKey);

        return $this;
    }

    public function setListKey(string $listKey): ViewOverlayListViewBuilderInterface
    {
        $this->setListKeyToView($this->view, $listKey);

        return $this;
    }

    public function setTitle(string $title): ViewOverlayListViewBuilderInterface
    {
        $this->setTitleToView($this->view, $title);

        return $this;
    }

    public function setAddOverlayTitle(string $addOverlayTitle): ViewOverlayListViewBuilderInterface
    {
        $this->view->setOption('addOverlayTitle', $addOverlayTitle);

        return $this;
    }

    public function setEditOverlayTitle(string $editOverlayTitle): ViewOverlayListViewBuilderInterface
    {
        $this->view->setOption('editOverlayTitle', $editOverlayTitle);

        return $this;
    }

    public function setAddOverlayView(string $addOverlayView): ViewOverlayListViewBuilderInterface
    {
        $this->view->setOption('addOverlayView', $addOverlayView);

        return $this;
    }

    public function setEditOverlayView(string $editOverlayView): ViewOverlayListViewBuilderInterface
    {
        $this->view->setOption('editOverlayView', $editOverlayView);

        return $this;
    }

    public function setTabTitle(string $tabTitle): ViewOverlayListViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): ViewOverlayListViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): ViewOverlayListViewBuilderInterface
    {
        $this->setTabPriorityToView($this->view, $tabPriority);

        return $this;
    }

    public function setTabCondition(string $tabCondition): ViewOverlayListViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): ViewOverlayListViewBuilderInterface
    {
        $this->addListAdaptersToView($this->view, $listAdapters);

        return $this;
    }

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): ViewOverlayListViewBuilderInterface
    {
        $this->addLocalesToView($this->view, $locales);

        return $this;
    }

    public function setDefaultLocale(string $locale): ViewOverlayListViewBuilderInterface
    {
        $this->setDefaultLocaleToView($this->view, $locale);

        return $this;
    }

    public function setItemDisabledCondition(string $itemDisabledCondition): ViewOverlayListViewBuilderInterface
    {
        $this->setItemDisabledConditionToView($this->view, $itemDisabledCondition);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): ViewOverlayListViewBuilderInterface
    {
        $this->addToolbarActionsToView($this->view, $toolbarActions);

        return $this;
    }

    public function addItemActions(array $itemActions): ViewOverlayListViewBuilderInterface
    {
        $this->addItemActionsToView($this->view, $itemActions);

        return $this;
    }

    public function setBackView(string $backView): ViewOverlayListViewBuilderInterface
    {
        $this->setBackViewToView($this->view, $backView);

        return $this;
    }

    public function enableSearching(): ViewOverlayListViewBuilderInterface
    {
        $this->setSearchableToView($this->view, true);

        return $this;
    }

    public function disableSearching(): ViewOverlayListViewBuilderInterface
    {
        $this->setSearchableToView($this->view, false);

        return $this;
    }

    public function enableSelection(): ViewOverlayListViewBuilderInterface
    {
        $this->setSelectableToView($this->view, true);

        return $this;
    }

    public function disableSelection(): ViewOverlayListViewBuilderInterface
    {
        $this->setSelectableToView($this->view, false);

        return $this;
    }

    public function enableColumnOptions(): ViewOverlayListViewBuilderInterface
    {
        $this->setHideColumnOptionsToView($this->view, false);

        return $this;
    }

    public function disableColumnOptions(): ViewOverlayListViewBuilderInterface
    {
        $this->setHideColumnOptionsToView($this->view, true);

        return $this;
    }

    public function enableFiltering(): ViewOverlayListViewBuilderInterface
    {
        $this->setFilterableToView($this->view, true);

        return $this;
    }

    public function disableFiltering(): ViewOverlayListViewBuilderInterface
    {
        $this->setFilterableToView($this->view, false);

        return $this;
    }

    public function addAdapterOptions(array $adapterOptions): ViewOverlayListViewBuilderInterface
    {
        $this->addAdapterOptionsToView($this->view, $adapterOptions);

        return $this;
    }

    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): ViewOverlayListViewBuilderInterface
    {
        $this->addRouterAttributesToListRequestToView($this->view, $routerAttributesToListRequest);

        return $this;
    }

    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): ViewOverlayListViewBuilderInterface
    {
        $this->addResourceStorePropertiesToListRequestToView($this->view, $resourceStorePropertiesToListRequest);

        return $this;
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function addRequestParameters(array $requestParameters): ViewOverlayListViewBuilderInterface
    {
        $this->addRequestParametersToView($this->view, $requestParameters);

        return $this;
    }

    public function setOverlaySize(string $overlaySize): ViewOverlayListViewBuilderInterface
    {
        $this->view->setOption('overlaySize', $overlaySize);

        return $this;
    }

    public function addTabBadges(array $badges): ViewOverlayListViewBuilderInterface
    {
        $this->addTabBadgesToView($this->view, $badges);

        return $this;
    }

    public function getView(): View
    {
        if (!$this->view->getOption('resourceKey')) {
            throw new \DomainException(
                'A view for a view-overlay-list view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->view->getOption('listKey')) {
            throw new \DomainException(
                'A view for a view-overlay-list view needs a "listKey" option.'
                . ' You have likely forgotten to call the "setListKey" method.'
            );
        }

        if (!$this->view->getOption('adapters')) {
            throw new \DomainException(
                'A view for a view-overlay-list needs a "adapters" option.'
                . ' You have likely forgotten to call the "addListAdapters" method.'
            );
        }

        if ($this->view->getOption('locales') && false === \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a view-overlay-list needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->view->getOption('locales') && false !== \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a view-overlay-list cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->view;
    }
}
