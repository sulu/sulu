<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

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

class ContentAdmin extends Admin
{
    /**
     * The prefix for the security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    const WEBSPACES_ROUTE = 'sulu_content.webspaces';

    const ADD_FORM_ROUTE = 'sulu_content.page_add_form';

    const EDIT_FORM_ROUTE = 'sulu_content.page_edit_form';

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
                $webspaceItem = new NavigationItem('sulu_content.webspaces');
                $webspaceItem->setPosition(10);
                $webspaceItem->setIcon('su-webspace');
                $webspaceItem->setMainRoute(static::WEBSPACES_ROUTE);

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
            'sulu_admin.type',
            'sulu_admin.delete',
            'sulu_content.edit',
        ];

        $formToolbarActionsWithoutType = [
            'sulu_admin.save_with_publishing',
        ];

        $routerAttributesToFormStore = ['parentId', 'webspace'];

        $previewExpression = 'nodeType == 1';

        return [
            (new Route(static::WEBSPACES_ROUTE, '/webspaces/:webspace/:locale', 'sulu_content.webspace_overview'))
                ->setAttributeDefault('webspace', $firstWebspace->getKey())
                ->setAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                ->addRerenderAttribute('webspace'),
            (new Route(static::ADD_FORM_ROUTE, '/webspaces/:webspace/:locale/add/:parentId', 'sulu_content.page_tabs'))
                ->setOption('backRoute', static::WEBSPACES_ROUTE)
                ->setOption('resourceKey', 'pages'),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_content.page_add_form.detail', '/details')
                ->setResourceKey('pages')
                ->setFormKey('pages')
                ->setTabTitle('sulu_content.page_form_detail')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addRouterAttributesToEditRoute(['webspace'])
                ->addToolbarActions($formToolbarActionsWithType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute(),
            (new Route(static::EDIT_FORM_ROUTE, '/webspaces/:webspace/:locale/:id', 'sulu_content.page_tabs'))
                ->setOption('backRoute', static::WEBSPACES_ROUTE)
                ->setOption('resourceKey', 'pages'),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_content.page_edit_form.detail', '/details')
                ->setResourceKey('pages')
                ->setFormKey('pages')
                ->setTabTitle('sulu_content.page_form_detail')
                ->addToolbarActions($formToolbarActionsWithType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setPreviewCondition($previewExpression)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_content.page_edit_form.seo', '/seo')
                ->setFormKey('pages_seo')
                ->setResourceKey('pages_seo')
                ->setTabTitle('sulu_content.page_form_seo')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_content.page_edit_form.excerpt', '/excerpt')
                ->setResourceKey('pages_excerpt')
                ->setFormKey('pages_excerpt')
                ->setBackRoute(static::WEBSPACES_ROUTE)
                ->setTabTitle('sulu_content.page_form_excerpt')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_content.page_edit_form.settings', '/settings')
                ->setResourceKey('pages_settings')
                ->setFormKey('pages_settings')
                ->setBackRoute(static::WEBSPACES_ROUTE)
                ->setTabTitle('sulu_content.page_form_settings')
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
