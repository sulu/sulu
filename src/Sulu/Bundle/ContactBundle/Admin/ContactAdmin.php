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
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContactAdmin extends Admin
{
    const CONTACT_DATAGRID_ROUTE = 'sulu_contact.contacts_datagrid';

    const CONTACT_ADD_FORM_ROUTE = 'sulu_contact.contact_add_form';

    const CONTACT_EDIT_FORM_ROUTE = 'sulu_contact.contact_edit_form';

    const ACCOUNT_DATAGRID_ROUTE = 'sulu_contact.accounts_datagrid';

    const ACCOUNT_ADD_FORM_ROUTE = 'sulu_contact.account_add_form';

    const ACCOUNT_EDIT_FORM_ROUTE = 'sulu_contact.account_edit_form';

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

    public function getNavigationItemContacts(): NavigationItem
    {
        $contacts = new NavigationItem('sulu_contact.contacts');
        $contacts->setPosition(40);
        $contacts->setIcon('su-user');

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
        $formToolbarActions = [
            'sulu_admin.save',
        ];

        $formToolbarActionsWithDelete = [
            'sulu_admin.save',
            'sulu_admin.delete',
        ];

        return [
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::CONTACT_DATAGRID_ROUTE, '/contacts')
                ->setResourceKey('contacts')
                ->setTitle('sulu_contact.people')
                ->addDatagridAdapters(['table'])
                ->setAddRoute(static::CONTACT_ADD_FORM_ROUTE)
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute(),
            (new Route(static::CONTACT_ADD_FORM_ROUTE, '/contacts/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'contacts')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_contact.contact_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('formKey', 'contacts')
                ->addOption('backRoute', static::CONTACT_DATAGRID_ROUTE)
                ->addOption('editRoute', 'sulu_contact.contact_edit_form.detail')
                ->addOption('toolbarActions', $formToolbarActionsWithDelete)
                ->setParent(static::CONTACT_ADD_FORM_ROUTE),
            (new Route(static::CONTACT_EDIT_FORM_ROUTE, '/contacts/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'contacts')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_contact.contact_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('formKey', 'contacts')
                ->addOption('backRoute', static::CONTACT_DATAGRID_ROUTE)
                ->addOption('toolbarActions', $formToolbarActionsWithDelete)
                ->setParent(static::CONTACT_EDIT_FORM_ROUTE),
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::ACCOUNT_DATAGRID_ROUTE, '/accounts')
                ->setResourceKey('accounts')
                ->setTitle('sulu_contact.organizations')
                ->addDatagridAdapters(['table'])
                ->setAddRoute(static::ACCOUNT_ADD_FORM_ROUTE)
                ->setEditRoute(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->getRoute(),
            (new Route(static::ACCOUNT_ADD_FORM_ROUTE, '/accounts/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'accounts')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_contact.account_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('formKey', 'accounts')
                ->addOption('backRoute', static::ACCOUNT_DATAGRID_ROUTE)
                ->addOption('editRoute', 'sulu_contact.account_edit_form.detail')
                ->addOption('toolbarActions', $formToolbarActionsWithDelete)
                ->setParent(static::ACCOUNT_ADD_FORM_ROUTE),
            (new Route(static::ACCOUNT_EDIT_FORM_ROUTE, '/accounts/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'accounts')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_contact.account_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_contact.details')
                ->addOption('formKey', 'accounts')
                ->addOption('backRoute', static::ACCOUNT_DATAGRID_ROUTE)
                ->addOption('toolbarActions', $formToolbarActionsWithDelete)
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE),
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
