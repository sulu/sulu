<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Integrates audience targeting into sulu-admin.
 */
class AudienceTargetingAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.settings.target-groups';

    const LIST_ROUTE = 'sulu_audience_targeting.list';

    const ADD_FORM_ROUTE = 'sulu_audience_targeting.add_form';

    const EDIT_FORM_ROUTE = 'sulu_audience_targeting.edit_form';

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        $settings = Admin::getNavigationItemSettings();

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $targetGroups = new NavigationItem('sulu_audience_targeting.target_groups');
            $targetGroups->setPosition(10);
            $targetGroups->setMainRoute(static::LIST_ROUTE);
            $settings->addChild($targetGroups);
        }

        if ($settings->hasChildren()) {
            $rootNavigationItem->addChild($settings);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        $listToolbarActions = [
            'sulu_admin.add',
            'sulu_admin.delete',
            'sulu_admin.export',
        ];

        $formToolbarActions = [
            'sulu_admin.save',
            'sulu_admin.delete',
        ];

        return [
            $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/target-groups')
                ->setResourceKey('target_groups')
                ->setListKey('target_groups')
                ->setTitle('sulu_audience_targeting.target_groups')
                ->addListAdapters(['table'])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/target-groups/add')
                ->setResourceKey('target_groups')
                ->setBackRoute(static::LIST_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_audience_targeting.add_form.details', '/details')
                ->setResourceKey('target_groups')
                ->setFormKey('target_group_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/target-groups/:id')
                ->setResourceKey('target_groups')
                ->setBackRoute(static::LIST_ROUTE)
                ->setTitleProperty('title')
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_audience_targeting.edit_form.details', '/details')
                ->setResourceKey('target_groups')
                ->setFormKey('target_group_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Settings' => [
                    self::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }
}
