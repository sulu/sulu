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
use Symfony\Component\Translation\TranslatorInterface;

class SecurityAdmin extends Admin
{
    const ROLE_SECURITY_CONTEXT = 'sulu.security.roles';

    const GROUP_SECURITY_CONTEXT = 'sulu.security.groups';

    const USER_SECURITY_CONTEXT = 'sulu.security.users';

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

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $resources;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        array $resources
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->resources = $resources;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        $settings = Admin::getNavigationItemSettings();

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
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
                    static::ROLE_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    static::GROUP_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    static::USER_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];
    }

    public function getRoutes(): array
    {
        $formToolbarActions = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = 'sulu_admin.add';
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = 'sulu_admin.save';
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = 'sulu_admin.delete';
            $listToolbarActions[] = 'sulu_admin.delete';
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = 'sulu_admin.export';
        }

        $routes = [];

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $routes[] = $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/roles')
                ->setResourceKey('roles')
                ->setListKey('roles')
                ->setTitle('sulu_security.roles')
                ->addListAdapters(['table'])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/roles/add')
                ->setResourceKey('roles')
                ->setBackRoute(static::LIST_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_security.role_add_form.details', '/details')
                ->setResourceKey('roles')
                ->setFormKey('role_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/roles/:id')
                ->setResourceKey('roles')
                ->setBackRoute(static::LIST_ROUTE)
                ->setTitleProperty('name')
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_security.role_edit_form.details', '/details')
                ->setResourceKey('roles')
                ->setFormKey('role_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute();
        }

        if ($this->securityChecker->hasPermission(static::USER_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_security.form.permissions', '/permissions')
                ->setResourceKey('users')
                ->setFormKey('user_details')
                ->setTabTitle('sulu_security.permissions')
                ->addToolbarActions([
                    'sulu_admin.save',
                    'sulu_security.enable_user',
                    'sulu_admin.toggler' => [
                        'label' => $this->translator->trans('sulu_security.user_locked', [], 'admin'),
                        'property' => 'locked',
                        'activate' => 'lock',
                        'deactivate' => 'unlock',
                    ],
                ])
                ->setIdQueryParameter('contactId')
                ->setTitleVisible(true)
                ->setTabOrder(3072)
                ->setParent(ContactAdmin::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute();
        }

        return $routes;
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
            'resourceKeySecurityContextMapping' => array_filter(array_map(function(array $resource) {
                return $resource['security_context'] ?? null;
            }, $this->resources)),
        ];
    }
}
