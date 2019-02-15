<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class PageAdmin extends Admin
{
    /**
     * The prefix for the security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    const WEBSPACE_TABS_ROUTE = 'sulu_page.webspaces';

    const PAGES_ROUTE = 'sulu_page.pages_datagrid';

    const ADD_FORM_ROUTE = 'sulu_page.page_add_form';

    const EDIT_FORM_ROUTE = 'sulu_page.page_edit_form';

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        SessionManagerInterface $sessionManager
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
        $this->sessionManager = $sessionManager;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT_PREFIX . $webspace->getKey(), PermissionTypes::VIEW)) {
                $webspaceItem = new NavigationItem('sulu_page.webspaces');
                $webspaceItem->setPosition(10);
                $webspaceItem->setIcon('su-webspace');
                $webspaceItem->setMainRoute(static::WEBSPACE_TABS_ROUTE);

                $rootNavigationItem->addChild($webspaceItem);

                break;
            }
        }

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        /** @var Webspace $firstWebspace */
        $firstWebspace = current($this->webspaceManager->getWebspaceCollection()->getWebspaces());

        $formToolbarActionsWithType = [
            'sulu_admin.save_with_publishing',
            'sulu_page.templates',
            'sulu_admin.delete',
            'sulu_page.edit',
        ];

        $formToolbarActionsWithoutType = [
            'sulu_admin.save_with_publishing',
        ];

        $routerAttributesToFormStore = ['parentId', 'webspace'];

        $previewExpression = 'nodeType == 1';

        return [
            $this->routeBuilderFactory->createTabRouteBuilder(static::WEBSPACE_TABS_ROUTE, '/webspaces/:webspace')
                ->getRoute()
                ->setAttributeDefault('webspace', $firstWebspace->getKey()),
            (new Route(static::PAGES_ROUTE, '/pages/:locale', 'sulu_page.webspace_overview'))
                ->setAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                ->setOption('tabTitle', 'sulu_page.content')
                ->addRerenderAttribute('webspace')
                ->setParent(static::WEBSPACE_TABS_ROUTE),
            (new Route(static::ADD_FORM_ROUTE, '/webspaces/:webspace/:locale/add/:parentId', 'sulu_page.page_tabs'))
                ->setOption('backRoute', static::PAGES_ROUTE)
                ->setOption('resourceKey', 'pages'),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_page.page_add_form.details', '/details')
                ->setResourceKey('pages')
                ->setFormKey('page')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addRouterAttributesToEditRoute(['webspace'])
                ->addToolbarActions($formToolbarActionsWithType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute(),
            (new Route(static::EDIT_FORM_ROUTE, '/webspaces/:webspace/:locale/:id', 'sulu_page.page_tabs'))
                ->setOption('backRoute', static::PAGES_ROUTE)
                ->setOption('resourceKey', 'pages'),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_page.page_edit_form.details', '/details')
                ->setResourceKey('pages')
                ->setFormKey('page')
                ->setTabTitle('sulu_admin.details')
                ->setTabPriority(1024)
                ->setTabCondition('nodeType == 1 && shadowOn == false')
                ->addToolbarActions($formToolbarActionsWithType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setPreviewCondition($previewExpression)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_page.page_edit_form.seo', '/seo')
                ->setResourceKey('pages_seo')
                ->setFormKey('page_seo')
                ->setTabTitle('sulu_page.seo')
                ->setTabCondition('nodeType == 1 && shadowOn == false')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_page.page_edit_form.excerpt', '/excerpt')
                ->setResourceKey('pages_excerpt')
                ->setFormKey('page_excerpt')
                ->setBackRoute(static::PAGES_ROUTE)
                ->setTabTitle('sulu_page.excerpt')
                ->setTabCondition('(nodeType == 1 || nodeType == 4) && shadowOn == false')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_page.page_edit_form.settings', '/settings')
                ->setResourceKey('pages')
                ->setFormKey('page_settings')
                ->setBackRoute(static::PAGES_ROUTE)
                ->setTabTitle('sulu_page.settings')
                ->setTabPriority(512)
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /* @var Webspace $webspace */
            $webspaceContexts[self::SECURITY_CONTEXT_PREFIX . $webspace->getKey()] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
                PermissionTypes::SECURITY,
            ];
        }

        return [
            'Sulu' => [
                'Webspaces' => $webspaceContexts,
            ],
        ];
    }

    public function getSecurityContextsWithPlaceholder()
    {
        return [
            'Sulu' => [
                'Webspaces' => [
                    self::SECURITY_CONTEXT_PREFIX . '#webspace#' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::LIVE,
                        PermissionTypes::SECURITY,
                    ],
                ],
            ],
        ];
    }
}
