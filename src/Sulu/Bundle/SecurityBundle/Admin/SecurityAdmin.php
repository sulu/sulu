<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityAdmin extends Admin
{
    const LIST_ROUTE = 'sulu_security.roles_list';

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

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->urlGenerator = $urlGenerator;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        $settings = Admin::getNavigationItemSettings();

        if ($this->securityChecker->hasPermission('sulu.security.roles', PermissionTypes::VIEW)) {
            $roles = new NavigationItem('sulu_security.roles', $settings);
            $roles->setPosition(10);
            $roles->setMainRoute(static::LIST_ROUTE);
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

        $listToolbarActions = [
            'sulu_admin.add',
            'sulu_admin.delete',
            'sulu_admin.export',
        ];

        return [
            $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/roles')
                ->setResourceKey('roles')
                ->setListKey('roles')
                ->setTitle('sulu_security.roles')
                ->addListAdapters(['table'])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/roles/add')
                ->setResourceKey('roles')
                ->setBackRoute(static::LIST_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_security.role_add_form.details', '/details')
                ->setResourceKey('roles')
                ->setFormKey('role_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/roles/:id')
                ->setResourceKey('roles')
                ->setBackRoute(static::LIST_ROUTE)
                ->setTitleProperty('name')
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_security.role_edit_form.details', '/details')
                ->setResourceKey('roles')
                ->setFormKey('role_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_security.form.permissions', '/permissions')
                ->setResourceKey('users')
                ->setFormKey('user_details')
                ->setTabTitle('sulu_security.permissions')
                ->addToolbarActions(['sulu_admin.save'])
                ->setIdQueryParameter('contactId')
                ->setTitleVisible(true)
                ->setTabOrder(3072)
                ->setParent(ContactAdmin::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute(),
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_security';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'contexts' => $this->urlGenerator->generate('cget_contexts'),
            ],
        ];
    }
}
