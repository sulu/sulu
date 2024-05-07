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
    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker
    ) {
    }

    public function createActivityListViewBuilder(
        string $name,
        string $path,
        string $resourceKey,
        string $resourceIdRouterAttribute = 'id'
    ): ListViewBuilderInterface {
        /** @var ListViewBuilderInterface $activityListViewBuilder */
        $activityListViewBuilder = $this->viewBuilderFactory
            ->createListViewBuilder($name, $path)
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
            ->disableTabGap()
            ->disableSearching()
            ->disableSelection()
            ->disableColumnOptions()
            ->disableFiltering()
            ->addRouterAttributesToListRequest([$resourceIdRouterAttribute => 'resourceId'])
            ->addRequestParameters(['resourceKey' => $resourceKey]);

        return $activityListViewBuilder;
    }

    public function hasActivityListPermission(): bool
    {
        return $this->securityChecker->hasPermission(ActivityAdmin::SECURITY_CONTEXT, PermissionTypes::VIEW);
    }
}
