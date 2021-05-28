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
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilder;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ActivityViewBuilder extends ListViewBuilder
{
    public function __construct(string $parent, string $resourceKey, string $resourceIdRouterAttribute = 'id')
    {
        parent::__construct($parent . '.activity', '/activities');

        $this
            ->setResourceKey(ActivityInterface::RESOURCE_KEY)
            ->setListKey('activities')
            ->setTabTitle('sulu_admin.activity')
            ->addListAdapters(['table'])
            ->addAdapterOptions([
                'table' => [
                    'skin' => 'flat',
                    'show_header' => true,
                ],
            ])
            ->setTabOrder(4096)
            ->disableSearching()
            ->disableSelection()
            ->disableColumnOptions()
            ->disableFiltering()
            ->addRouterAttributesToListRequest([$resourceIdRouterAttribute => 'resourceId'])
            ->addRequestParameters(['resourceKey' => $resourceKey])
            ->setParent($parent)
        ;
    }

    public static function hasPermission(SecurityCheckerInterface $securityChecker): bool
    {
        return $securityChecker->hasPermission(ActivityAdmin::SECURITY_CONTEXT, PermissionTypes::VIEW);
    }
}
