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

use Doctrine\Common\Persistence\ManagerRegistry;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContactAdmin extends Admin
{
    const CONTACT_SECURITY_CONTEXT = 'sulu.contact.people';

    const ACCOUNT_SECURITY_CONTEXT = 'sulu.contact.organizations';

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

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        ManagerRegistry $managerRegistry
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->managerRegistry = $managerRegistry;
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

        if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $people = new NavigationItem('sulu_contact.people');
            $people->setPosition(10);
            $people->setMainRoute(static::CONTACT_LIST_ROUTE);

            $contacts->addChild($people);
        }

        if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
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
        $contactEditFormRoute = $this->routeBuilderFactory
            ->createResourceTabRouteBuilder(static::CONTACT_EDIT_FORM_ROUTE, '/contacts/:id')
            ->setResourceKey('contacts')
            ->setBackRoute(static::CONTACT_LIST_ROUTE)
            ->setTitleProperty('fullName')
            ->getRoute();

        if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $contactFormToolbarActions = [];
            $contactListToolbarActions = [];
            $contactDocumentsToolbarAction = [];

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::ADD)) {
                $contactListToolbarActions[] = 'sulu_admin.add';
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
                $contactFormToolbarActions[] = 'sulu_admin.save';
                $contactDocumentsToolbarAction[] = 'sulu_contact.add_media';
                $contactDocumentsToolbarAction[] = 'sulu_contact.delete_media';
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::DELETE)) {
                $contactFormToolbarActions[] = 'sulu_admin.delete';
                $contactListToolbarActions[] = 'sulu_admin.delete';
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::VIEW)) {
                $contactListToolbarActions[] = 'sulu_admin.export';
            }

            $routes[] = $this->routeBuilderFactory->createListRouteBuilder(static::CONTACT_LIST_ROUTE, '/contacts')
                ->setResourceKey('contacts')
                ->setListKey('contacts')
                ->setTitle('sulu_contact.people')
                ->addListAdapters(['table'])
                ->setAddRoute(static::CONTACT_ADD_FORM_ROUTE)
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->addToolbarActions($contactListToolbarActions)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createResourceTabRouteBuilder(static::CONTACT_ADD_FORM_ROUTE, '/contacts/add')
                ->setResourceKey('contacts')
                ->setBackRoute(static::CONTACT_LIST_ROUTE)
                ->getRoute();
            $routes[] = $contactEditFormRoute;
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_contact.contact_add_form.details', '/details')
                ->setResourceKey('contacts')
                ->setFormKey('contact_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->addToolbarActions($contactFormToolbarActions)
                ->setParent(static::CONTACT_ADD_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_contact.contact_edit_form.details', '/details')
                ->setResourceKey('contacts')
                ->setFormKey('contact_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($contactFormToolbarActions)
                ->setTabOrder(1024)
                ->setParent(static::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createListRouteBuilder('sulu_contact.contact_documents_list', '/documents')
                ->setResourceKey('contact_media')
                ->setListKey('media')
                ->setUserSettingsKey('contact_media')
                ->setTabTitle('sulu_contact.documents')
                ->addListAdapters(['table'])
                ->addToolbarActions($contactDocumentsToolbarAction)
                ->addRouterAttributesToListStore(['id' => 'contactId'])
                ->setTabOrder(2048)
                ->setParent(static::CONTACT_EDIT_FORM_ROUTE)
                ->getRoute();
        } else {
            // This route has to be registered even if permissions for contacts are missing
            // Otherwise the application breaks when other bundles try to add child routes to this one
            $routes[] = $contactEditFormRoute;
        }

        if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $accountFormToolbarActions = [];
            $accountListToolbarActions = [];
            $accountDocumentsToolbarAction = [];

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::ADD)) {
                $accountListToolbarActions[] = 'sulu_admin.add';
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
                $accountFormToolbarActions[] = 'sulu_admin.save';
                $accountDocumentsToolbarAction[] = 'sulu_contact.add_media';
                $accountDocumentsToolbarAction[] = 'sulu_contact.delete_media';
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::DELETE)) {
                $accountFormToolbarActions[] = 'sulu_admin.delete';
                $accountListToolbarActions[] = 'sulu_admin.delete';
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::VIEW)) {
                $accountListToolbarActions[] = 'sulu_admin.export';
            }

            $routes[] = $this->routeBuilderFactory->createListRouteBuilder(static::ACCOUNT_LIST_ROUTE, '/accounts')
                ->setResourceKey('accounts')
                ->setListKey('accounts')
                ->setTitle('sulu_contact.organizations')
                ->addListAdapters(['table'])
                ->setAddRoute(static::ACCOUNT_ADD_FORM_ROUTE)
                ->setEditRoute(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->addToolbarActions($accountListToolbarActions)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createResourceTabRouteBuilder(static::ACCOUNT_ADD_FORM_ROUTE, '/accounts/add')
                ->setResourceKey('accounts')
                ->setBackRoute(static::ACCOUNT_LIST_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_contact.account_add_form.details', '/details')
                ->setResourceKey('accounts')
                ->setFormKey('account_details')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->addToolbarActions($accountFormToolbarActions)
                ->setParent(static::ACCOUNT_ADD_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createResourceTabRouteBuilder(static::ACCOUNT_EDIT_FORM_ROUTE, '/accounts/:id')
                ->setResourceKey('accounts')
                ->setBackRoute(static::ACCOUNT_LIST_ROUTE)
                ->setTitleProperty('name')
                ->addRouterAttributesToBlacklist(['active', 'limit', 'page', 'search', 'sortColumn', 'sortOrder'])
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_contact.account_edit_form.details', '/details')
                ->setResourceKey('accounts')
                ->setFormKey('account_details')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($accountFormToolbarActions)
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->setTabOrder(1024)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createListRouteBuilder('sulu_contact.account_contacts_list', '/contacts')
                ->setResourceKey('account_contacts')
                ->setListKey('account_contacts')
                ->setTabTitle(static::CONTACT_SECURITY_CONTEXT)
                ->addListAdapters(['table'])
                ->setEditRoute(static::CONTACT_EDIT_FORM_ROUTE)
                ->addRouterAttributesToListStore(['id'])
                ->addToolbarActions(['sulu_contact.add_contact', 'sulu_admin.delete'])
                ->addRouterAttributesToListStore(['id' => 'accountId'])
                ->setTabOrder(2048)
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createListRouteBuilder('sulu_contact.account_documents_list', '/documents')
                ->setResourceKey('account_media')
                ->setListKey('media')
                ->setUserSettingsKey('contact_media')
                ->setTabTitle('sulu_contact.documents')
                ->addListAdapters(['table'])
                ->addRouterAttributesToListStore(['id' => 'contactId'])
                ->addToolbarActions($accountDocumentsToolbarAction)
                ->setTabOrder(3072)
                ->setParent(static::ACCOUNT_EDIT_FORM_ROUTE)
                ->getRoute();
        }

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Contacts' => [
                    static::CONTACT_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    static::ACCOUNT_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_contact';
    }

    public function getConfig(): ?array
    {
        return [
            'addressTypes' => $this->managerRegistry->getRepository('SuluContactBundle:AddressType')->findAll(),
            'countries' => $this->managerRegistry->getRepository('SuluContactBundle:Country')->findAll(),
            'emailTypes' => $this->managerRegistry->getRepository('SuluContactBundle:EmailType')->findAll(),
            'faxTypes' => $this->managerRegistry->getRepository('SuluContactBundle:FaxType')->findAll(),
            'phoneTypes' => $this->managerRegistry->getRepository('SuluContactBundle:PhoneType')->findAll(),
            'socialMediaTypes' => $this->managerRegistry
                ->getRepository('SuluContactBundle:SocialMediaProfileType')->findAll(),
            'websiteTypes' => $this->managerRegistry->getRepository('SuluContactBundle:UrlType')->findAll(),
        ];
    }
}
