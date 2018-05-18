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

    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $this->securityChecker = $securityChecker;

        if (!$this->securityChecker) {
            return;
        }

        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('navigation.modules');
        $section->setPosition(20);

        $settings = new NavigationItem('navigation.settings');
        $settings->setPosition(40);
        $settings->setIcon('gear');

        if ($this->securityChecker->hasPermission('sulu.security.roles', PermissionTypes::VIEW)) {
            $roles = new NavigationItem('security.roles.title', $settings);
            $roles->setPosition(10);
            $roles->setAction('settings/roles');
            $roles->setIcon('gear');
        }

        if ($settings->hasChildren()) {
            $section->addChild($settings);
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    public function getNavigationV2(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        $settings = Admin::getNavigationItemSettings();

        if ($this->securityChecker->hasPermission('sulu.security.roles', PermissionTypes::VIEW)) {
            $roles = new NavigationItem('sulu_security.roles', $settings);
            $roles->setPosition(10);
            $roles->setMainRoute('sulu_security.datagrid');
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
        return [
            (new Route('sulu_security.datagrid', '/roles', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_security.roles')
                ->addOption('adapters', ['table'])
                ->addOption('resourceKey', 'roles'),
            (new Route('sulu_security.form.permissions', '/permissions', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_security.permissions')
                ->addOption('backRoute', 'sulu_contact.contacts_datagrid')
                ->addOption('resourceKey', 'users')
                ->addOption('idQueryParameter', 'contactId')
                ->setParent('sulu_contact.contact_edit_form'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulusecurity';
    }
}
