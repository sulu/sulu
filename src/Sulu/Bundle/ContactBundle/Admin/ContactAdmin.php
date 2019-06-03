<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContactAdmin extends Admin
{
    const CONTACT_LIST_ROUTE = 'sulu_contact.contacts_list';

    const CONTACT_ADD_FORM_ROUTE = 'sulu_contact.contact_add_form';

    const CONTACT_EDIT_FORM_ROUTE = 'sulu_contact.contact_edit_form';

    const ACCOUNT_LIST_ROUTE = 'sulu_contact.accounts_list';

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
            $people->setMainRoute(static::CONTACT_LIST_ROUTE);

            $contacts->addChild($people);
        }

        if ($this->securityChecker->hasPermission('sulu.contact.organizations', PermissionTypes::VIEW)) {
            $companies = new NavigationItem('sulu_contact.organizations');
            $companies->setPosition(20);
            $companies->setMainRoute(static::ACCOUNT_LIST_ROUTE);

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
            'sulu_admin.delete',
        ];

        $listToolbarActions = [
            'sulu_admin.add',
            'sulu_admin.delete',
            'sulu_admin.export',
        ];

        $documentsToolbarAction = [
            'sulu_contact.add_media',
            'sulu_contact.delete_media',
        ];

        return [
            $this->routeBuilderFactory->createListRouteBuilder(static::CONTACT_LIST_ROUTE, '/contacts')
                ->setResourceKey('contacts')
                ->setListKey('contacts')
                ->setTitle('sulu_contact.people')
                ->addListAdapters(['table'])
                ->setAddRoute(static::CONTACT_ADD_FORM_ROUTE)
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::CONTACT_ADD_FORM_ROUTE, '/contacts/add')
                ->setResourceKey('contacts')
                ->setBackRoute(static::CONTACT_LIST_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_contact.contact_add_form.details', '/details')
                ->setResourceKey('contacts')
                ->setFormKey('contact_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::CONTACT_ADD_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::CONTACT_EDIT_FORM_ROUTE, '/contacts/:id')
                ->setResourceKey('contacts')
                ->setBackRoute(static::CONTACT_LIST_ROUTE)
                ->setTitleProperty('fullName')
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_contact.contact_edit_form.details', '/details')
                ->setResourceKey('contacts')
                ->setFormKey('contact_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createListRouteBuilder('sulu_contact.contact_documents_list', '/documents')
                ->setResourceKey('contact_media')
                ->setListKey('media')
                ->setUserSettingsKey('contact_media')
                ->setTabTitle('sulu_contact.documents')
                ->addListAdapters(['table'])
                ->addToolbarActions($documentsToolbarAction)
                ->addRouterAttributesToListStore(['id' => 'contactId'])
                ->setParent(static::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createListRouteBuilder(static::ACCOUNT_LIST_ROUTE, '/accounts')
                ->setResourceKey('accounts')
                ->setListKey('accounts')
                ->setTitle('sulu_contact.organizations')
                ->addListAdapters(['table'])
                ->setAddRoute(static::ACCOUNT_ADD_FORM_ROUTE)
                ->setEditRoute(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ACCOUNT_ADD_FORM_ROUTE, '/accounts/add')
                ->setResourceKey('accounts')
                ->setBackRoute(static::ACCOUNT_LIST_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_contact.account_add_form.details', '/details')
                ->setResourceKey('accounts')
                ->setFormKey('account_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::ACCOUNT_ADD_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ACCOUNT_EDIT_FORM_ROUTE, '/accounts/:id')
                ->setResourceKey('accounts')
                ->setBackRoute(static::ACCOUNT_LIST_ROUTE)
                ->setTitleProperty('name')
                ->addRouterAttributesToBlacklist(['sortColumn', 'sortOrder'])
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_contact.account_edit_form.details', '/details')
                ->setResourceKey('accounts')
                ->setFormKey('account_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createListRouteBuilder('sulu_contact.account_documents_list', '/documents')
                ->setResourceKey('account_media')
                ->setListKey('media')
                ->setUserSettingsKey('contact_media')
                ->setTabTitle('sulu_contact.documents')
                ->addListAdapters(['table'])
                ->addRouterAttributesToListStore(['id' => 'contactId'])
                ->addToolbarActions($documentsToolbarAction)
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createListRouteBuilder('sulu_contact.account_contacts_list', '/contacts')
                ->setResourceKey('account_contacts')
                ->setListKey('account_contacts')
                ->setTabTitle('sulu_contact.people')
                ->addListAdapters(['table'])
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->addRouterAttributesToListStore(['id'])
                ->addToolbarActions(['sulu_admin.delete'])
                ->addRouterAttributesToListStore(['id' => 'accountId'])
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE)
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
