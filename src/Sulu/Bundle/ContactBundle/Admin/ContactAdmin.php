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

    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $this->securityChecker = $securityChecker;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = new NavigationItem('root');

        $section = new NavigationItem('navigation.modules');
        $section->setPosition(20);

        $contacts = new NavigationItem('navigation.contacts');
        $contacts->setPosition(30);
        $contacts->setIcon('user');

        if ($this->securityChecker->hasPermission('sulu.contact.people', PermissionTypes::VIEW)) {
            $people = new NavigationItem('navigation.contacts.people');
            $people->setPosition(10);
            $people->setIcon('users');
            $people->setAction('contacts/contacts');
            $people->setMainRoute('sulu_contact.datagrid');

            $contacts->addChild($people);
        }

        if ($this->securityChecker->hasPermission('sulu.contact.organizations', PermissionTypes::VIEW)) {
            $companies = new NavigationItem('navigation.contacts.companies');
            $companies->setPosition(20);
            $companies->setIcon('building');
            $companies->setAction('contacts/accounts');
            $companies->setMainRoute('sulu_account.datagrid');

            $contacts->addChild($companies);
        }

        if ($contacts->hasChildren()) {
            $rootNavigationItem->addChild($section);
            $section->addChild($contacts);
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
                ->addOption('addRoute', 'sulu_contact.add_form.detail')
                ->addOption('editRoute', 'sulu_contact.edit_form.detail'),
            (new Route('sulu_contact.add_form', '/contacts/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'contacts'),
            (new Route('sulu_contact.add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('backRoute', 'sulu_contact.contacts_datagrid')
                ->addOption('editRoute', 'sulu_contact.edit_form.detail')
                ->setParent('sulu_contact.add_form'),
            (new Route('sulu_contact.edit_form', '/contacts/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'contacts'),
            (new Route('sulu_contact.edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('backRoute', 'sulu_contact.contacts_datagrid')
                ->setParent('sulu_contact.edit_form'),
            (new Route('sulu_contact.accounts_datagrid', '/accounts', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_contact.organizations')
                ->addOption('adapters', ['table'])
                ->addOption('resourceKey', 'accounts'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulucontact';
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
