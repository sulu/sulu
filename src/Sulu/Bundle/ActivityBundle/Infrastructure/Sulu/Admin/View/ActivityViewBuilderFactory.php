<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View;

use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\ActivityAdmin;
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ActivityViewBuilderFactory implements ActivityViewBuilderFactoryInterface
{
    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
    }

    public function createActivityListViewBuilder(
        string $parent,
        string $resourceKey,
        string $resourceIdRouterAttribute = 'id'
    ): ListViewBuilderInterface {
        /** @var ListViewBuilderInterface $activityListViewBuilder */
        $activityListViewBuilder = $this->viewBuilderFactory
            ->createListViewBuilder($parent . '.activity', '/activities')
            ->setResourceKey(ActivityInterface::RESOURCE_KEY)
            ->setListKey(ActivityInterface::LIST_KEY)
            ->setTabTitle('sulu_admin.activity')
            ->addListAdapters(['table'])
            ->addAdapterOptions([
                'table' => [
                    'skin' => 'flat',
                    'show_header' => false,
                ],
            ])
            ->setTabOrder(4096)
            ->disableTabGap()
            ->disableSearching()
            ->disableSelection()
            ->disableColumnOptions()
            ->disableFiltering()
            ->addRouterAttributesToListRequest([$resourceIdRouterAttribute => 'resourceId'])
            ->addRequestParameters(['resourceKey' => $resourceKey])
            ->setParent($parent);

        return $activityListViewBuilder;
    }

    public function hasPermissionForActivityListView(): bool
    {
        return $this->securityChecker->hasPermission(ActivityAdmin::SECURITY_CONTEXT, PermissionTypes::VIEW);
    }
}
