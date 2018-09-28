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
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class SecurityAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
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
            (new Route('sulu_security.roles_datagrid', '/roles', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_security.roles')
                ->addOption('adapters', ['table'])
                ->addOption('resourceKey', 'roles')
                ->addOption('addRoute', 'sulu_security.role_add_form.detail')
                ->addOption('editRoute', 'sulu_security.role_edit_form.detail'),
            (new Route('sulu_security.role_add_form', '/roles/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'roles')
                ->addOption('backRoute', 'sulu_security.roles_datagrid')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_security.role_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.role_form_detail')
                ->addOption('editRoute', 'sulu_security.role_edit_form.detail')
                ->addOption('toolbarActions', $formToolbarActions)
                ->setParent('sulu_security.role_add_form'),
            (new Route('sulu_security.role_edit_form', '/roles/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'roles')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_security.role_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.role_form_detail')
                ->addOption('backRoute', 'sulu_security.roles_datagrid')
                ->addOption('toolbarActions', $formToolbarActions)
                ->setParent('sulu_security.role_edit_form'),
            (new Route('sulu_security.form.permissions', '/permissions', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.permissions')
                ->addOption('backRoute', 'sulu_contact.contacts_datagrid')
                ->addOption('resourceKey', 'users')
                ->addOption('idQueryParameter', 'contactId')
                ->addOption('toolbarActions', ['sulu_admin.save'])
                ->setParent('sulu_contact.contact_edit_form'),
        ];
    }
}
