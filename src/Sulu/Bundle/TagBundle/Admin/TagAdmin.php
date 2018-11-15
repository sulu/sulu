<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class TagAdmin extends Admin
{
    const DATAGRID_ROUTE = 'sulu_tag.datagrid';

    const ADD_FORM_ROUTE = 'sulu_tag.add_form';

    const EDIT_FORM_ROUTE = 'sulu_tag.edit_form';

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

        if ($this->securityChecker->hasPermission('sulu.settings.tags', 'view')) {
            $roles = new NavigationItem('sulu_tag.tags', $settings);
            $roles->setPosition(30);
            $roles->setMainRoute('sulu_tag.datagrid');
        }

        if ($settings->hasChildren()) {
            $rootNavigationItem->addChild($settings);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        $formToolbarActions = [
            'sulu_admin.save',
            'sulu_admin.delete',
        ];

        return [
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::DATAGRID_ROUTE, '/tags')
                ->setResourceKey('tags')
                ->setTitle('sulu_tag.tags')
                ->addDatagridAdapters(['table'])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            (new Route(static::ADD_FORM_ROUTE, '/tags/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'tags')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_tag.add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_tag.details')
                ->addOption('formKey', 'tags')
                ->addOption('backRoute', static::DATAGRID_ROUTE)
                ->addOption('editRoute', 'sulu_tag.edit_form.detail')
                ->setParent(static::ADD_FORM_ROUTE),
            (new Route(static::EDIT_FORM_ROUTE, '/tags/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'tags')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_tag.edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_tag.details')
                ->addOption('formKey', 'tags')
                ->addOption('backRoute', static::DATAGRID_ROUTE)
                ->setParent(static::EDIT_FORM_ROUTE),
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
                    'sulu.settings.tags' => [
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
