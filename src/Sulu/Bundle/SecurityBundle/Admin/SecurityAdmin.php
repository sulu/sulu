<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class SecurityAdmin extends Admin
{
    const DATAGRID_ROUTE = 'sulu_security.roles_datagrid';

    const ADD_FORM_ROUTE = 'sulu_security.role_add_form';

    const EDIT_FORM_ROUTE = 'sulu_security.role_edit_form';

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

        if ($this->securityChecker->hasPermission('sulu.security.roles', PermissionTypes::VIEW)) {
            $roles = new NavigationItem('sulu_security.roles', $settings);
            $roles->setPosition(10);
            $roles->setMainRoute('sulu_security.roles_datagrid');
        }

        if ($settings->hasChildren()) {
            $rootNavigationItem->addChild($settings);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Security' => [
                    'sulu.security.roles' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    'sulu.security.groups' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    'sulu.security.users' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }

    public function getRoutes(): array
    {
        $formToolbarActions = [
            'sulu_admin.save',
        ];

        return [
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::DATAGRID_ROUTE, '/roles')
                ->setResourceKey('roles')
                ->setTitle('sulu_security.roles')
                ->addDatagridAdapters(['table'])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/roles/add')
                ->setResourceKey('roles')
                ->getRoute(),
            (new Route('sulu_security.role_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.role_form_detail')
                ->addOption('formKey', 'roles')
                ->addOption('backRoute', static::DATAGRID_ROUTE)
                ->addOption('editRoute', 'sulu_security.role_edit_form.detail')
                ->addOption('toolbarActions', $formToolbarActions)
                ->setParent(static::ADD_FORM_ROUTE),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/roles/:id')
                ->setResourceKey('roles')
                ->getRoute(),
            (new Route('sulu_security.role_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.role_form_detail')
                ->addOption('formKey', 'roles')
                ->addOption('backRoute', static::DATAGRID_ROUTE)
                ->addOption('toolbarActions', $formToolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE),
            (new Route('sulu_security.form.permissions', '/permissions', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.permissions')
                ->addOption('backRoute', ContactAdmin::CONTACT_DATAGRID_ROUTE)
                ->addOption('resourceKey', 'users')
                ->addOption('formKey', 'users')
                ->addOption('idQueryParameter', 'contactId')
                ->addOption('toolbarActions', ['sulu_admin.save'])
                ->setParent(ContactAdmin::CONTACT_EDIT_FORM_ROUTE),
        ];
    }
}
