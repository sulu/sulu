<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Domain\Model\VersionInterface;

class ContentVersionViewBuilderFactory implements ContentVersionViewBuilderFactoryInterface
{
    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker
    ) {
    }

    public function createContentVersionListViewBuilder(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        string $successView,
        string $resourceIdRouterAttribute = 'id'
    ): ListViewBuilderInterface {
        return $this->viewBuilderFactory
            ->createListViewBuilder($name, $path)
            ->setTabTitle('sulu_admin.versions')
            ->setResourceKey(VersionInterface::RESOURCE_KEY)
            ->setListKey($listKey)
            ->addListAdapters(['table'])
            ->addAdapterOptions([
                'table' => [
                    'skin' => 'flat',
                ],
            ])
            ->disableTabGap()
            ->disableSearching()
            ->disableSelection()
            ->disableColumnOptions()
            ->disableFiltering()
            ->addRouterAttributesToListRequest([$resourceIdRouterAttribute => 'id'])
            ->addRequestParameters(['listKey' => $listKey])
            ->addItemActions([
                new ListItemAction('restore_version', [
                    'type' => $resourceKey,
                    'success_view' => $successView,
                ]),
            ]);
    }

    public function hasContentVersionListPermission(): bool
    {
        // TODO implement permission check
        return true;
    }
}
