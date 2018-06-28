<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContactAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    public function getNavigationItemContacts(): NavigationItem
    {
        $contacts = new NavigationItem('sulu_contact.contacts');
        $contacts->setPosition(40);
        $contacts->setIcon('fa-user');

        return $contacts;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();
        $contacts = $this->getNavigationItemContacts();

        if ($this->securityChecker->hasPermission('sulu.contact.people', PermissionTypes::VIEW)) {
            $people = new NavigationItem('sulu_contact.people');
            $people->setPosition(10);
            $people->setMainRoute('sulu_contact.contacts_datagrid');

            $contacts->addChild($people);
        }

        if ($this->securityChecker->hasPermission('sulu.contact.organizations', PermissionTypes::VIEW)) {
            $companies = new NavigationItem('sulu_contact.organizations');
            $companies->setPosition(20);
            $companies->setMainRoute('sulu_contact.accounts_datagrid');

            $contacts->addChild($companies);
        }

        if ($contacts->hasChildren()) {
            $rootNavigationItem->addChild($contacts);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        return [
            (new Route('sulu_contact.contacts_datagrid', '/contacts', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_contact.persons')
                ->addOption('adapters', ['table'])
                ->addOption('resourceKey', 'contacts')
                ->addOption('addRoute', 'sulu_contact.contact_add_form.detail')
                ->addOption('editRoute', 'sulu_contact.contact_edit_form.detail'),
            (new Route('sulu_contact.contact_add_form', '/contacts/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'contacts'),
            (new Route('sulu_contact.contact_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('backRoute', 'sulu_contact.contacts_datagrid')
                ->addOption('editRoute', 'sulu_contact.contact_edit_form.detail')
                ->setParent('sulu_contact.contact_add_form'),
            (new Route('sulu_contact.contact_edit_form', '/contacts/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'contacts'),
            (new Route('sulu_contact.contact_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('backRoute', 'sulu_contact.contacts_datagrid')
                ->setParent('sulu_contact.contact_edit_form'),
            (new Route('sulu_contact.accounts_datagrid', '/accounts', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_contact.organizations')
                ->addOption('adapters', ['table'])
                ->addOption('resourceKey', 'accounts')
                ->addOption('addRoute', 'sulu_contact.account_add_form.detail')
                ->addOption('editRoute', 'sulu_contact.account_edit_form.detail'),
            (new Route('sulu_contact.account_add_form', '/accounts/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'accounts'),
            (new Route('sulu_contact.account_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('backRoute', 'sulu_contact.accounts_datagrid')
                ->addOption('editRoute', 'sulu_contact.account_edit_form.detail')
                ->setParent('sulu_contact.account_add_form'),
            (new Route('sulu_contact.account_edit_form', '/accounts/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'accounts'),
            (new Route('sulu_contact.account_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('backRoute', 'sulu_contact.accounts_datagrid')
                ->setParent('sulu_contact.account_edit_form'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Contacts' => [
                    'sulu.contact.people' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    'sulu.contact.organizations' => [
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
