<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\Routing\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteCollection;
use Sulu\Bundle\AdminBundle\Admin\Routing\ToolbarAction;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
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

    const PAGES_ROUTE = 'sulu_page.pages_list';

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

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var bool
     */
    private $versioningEnabled;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        SessionManagerInterface $sessionManager,
        TeaserProviderPoolInterface $teaserProviderPool,
        bool $versioningEnabled
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
        $this->sessionManager = $sessionManager;
        $this->teaserProviderPool = $teaserProviderPool;
        $this->versioningEnabled = $versioningEnabled;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->hasSomeWebspacePermission()) {
            $webspaceItem = new NavigationItem('sulu_page.webspaces');
            $webspaceItem->setPosition(10);
            $webspaceItem->setIcon('su-webspace');
            $webspaceItem->setMainRoute(static::WEBSPACE_TABS_ROUTE);

            $navigationItemCollection->add($webspaceItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureRoutes(RouteCollection $routeCollection): void
    {
        /** @var Webspace $firstWebspace */
        $firstWebspace = current($this->webspaceManager->getWebspaceCollection()->getWebspaces());
        $publishDisplayCondition = '(!_permissions || _permissions.live)';

        $formToolbarActionsWithType = [
            new ToolbarAction(
                'sulu_admin.save_with_publishing',
                [
                    'publish_display_condition' => '(!_permissions || _permissions.live)',
                    'save_display_condition' => '(!_permissions || _permissions.edit)',
                ]
            ),
            new ToolbarAction('sulu_page.templates'),
            new ToolbarAction(
                'sulu_admin.delete',
                [
                    'display_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                ]
            ),
            new DropdownToolbarAction(
                'sulu_admin.edit',
                'su-pen',
                [
                    new ToolbarAction(
                        'sulu_admin.copy_locale',
                        [
                            'display_condition' => '(!_permissions || _permissions.edit)',
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.delete_draft',
                        [
                            'display_condition' => $publishDisplayCondition,
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.set_unpublished',
                        [
                            'display_condition' => $publishDisplayCondition,
                        ]
                    ),
                ]
            ),
        ];

        $formToolbarActionsWithoutType = [
            new ToolbarAction('sulu_admin.save_with_publishing'),
        ];

        $routerAttributesToFormStore = ['parentId', 'webspace'];

        $previewCondition = 'nodeType == 1';

        // This route has to be registered even if permissions for pages are missing
        // Otherwise the application breaks when other bundles try to add child routes to this one
        $routeCollection->add(
            $this->routeBuilderFactory
                ->createRouteBuilder(static::WEBSPACE_TABS_ROUTE, '/webspaces/:webspace', 'sulu_page.webspace_tabs')
                ->setAttributeDefault('webspace', $firstWebspace->getKey())
        );

        if ($this->hasSomeWebspacePermission()) {
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createRouteBuilder(static::PAGES_ROUTE, '/pages/:locale', 'sulu_page.page_list')
                    ->setAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                    ->setOption('tabTitle', 'sulu_page.pages')
                    ->setOption('tabOrder', 0)
                    ->setOption('tabPriority', 1024)
                    ->addRerenderAttribute('webspace')
                    ->setParent(static::WEBSPACE_TABS_ROUTE)
            );
            $routeCollection->add(
                $this->routeBuilderFactory->createRouteBuilder(
                    static::ADD_FORM_ROUTE,
                    '/webspaces/:webspace/pages/:locale/add/:parentId',
                    'sulu_page.page_tabs'
                )
                    ->setOption('backRoute', static::PAGES_ROUTE)
                    ->setOption('routerAttributesToBackRoute', ['webspace'])
                    ->setOption('resourceKey', 'pages')
            );
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createFormRouteBuilder('sulu_page.page_add_form.details', '/details')
                    ->setResourceKey('pages')
                    ->setFormKey('page')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditRoute(static::EDIT_FORM_ROUTE)
                    ->addRouterAttributesToEditRoute(['webspace'])
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                    ->setParent(static::ADD_FORM_ROUTE)
            );
            $routeCollection->add(
                $this->routeBuilderFactory->createRouteBuilder(
                    static::EDIT_FORM_ROUTE,
                    '/webspaces/:webspace/pages/:locale/:id',
                    'sulu_page.page_tabs'
                )
                    ->setOption('backRoute', static::PAGES_ROUTE)
                    ->setOption('routerAttributesToBackRoute', ['id' => 'active', 'webspace'])
                    ->setOption('resourceKey', 'pages')
            );
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createPreviewFormRouteBuilder('sulu_page.page_edit_form.details', '/details')
                    ->setResourceKey('pages')
                    ->setFormKey('page')
                    ->setTabTitle('sulu_admin.details')
                    ->setTabPriority(1024)
                    ->setTabCondition('nodeType == 1 && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                    ->setPreviewCondition($previewCondition)
                    ->setTabOrder(1024)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createPreviewFormRouteBuilder('sulu_page.page_edit_form.seo', '/seo')
                    ->setResourceKey('pages')
                    ->setFormKey('page_seo')
                    ->setTabTitle('sulu_page.seo')
                    ->setTabCondition('nodeType == 1 && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(2048)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createPreviewFormRouteBuilder('sulu_page.page_edit_form.excerpt', '/excerpt')
                    ->setResourceKey('pages')
                    ->setFormKey('page_excerpt')
                    ->setTabTitle('sulu_page.excerpt')
                    ->setTabCondition('(nodeType == 1 || nodeType == 4) && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(3072)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createPreviewFormRouteBuilder('sulu_page.page_edit_form.settings', '/settings')
                    ->setResourceKey('pages')
                    ->setFormKey('page_settings')
                    ->setTabTitle('sulu_page.settings')
                    ->setTabPriority(512)
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormStore($routerAttributesToFormStore)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(4096)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
            $routeCollection->add(
                $this->routeBuilderFactory
                    ->createFormRouteBuilder('sulu_page.page_edit_form.permissions', '/permissions')
                    ->setResourceKey('permissions')
                    ->setFormKey('permission_details')
                    ->setApiOptions(['resourceKey' => 'pages'])
                    ->setTabCondition('_permissions.security')
                    ->setTabTitle('sulu_security.permissions')
                    ->addToolbarActions([new ToolbarAction('sulu_admin.save')])
                    ->addRouterAttributesToFormStore(['webspace'])
                    ->setTitleVisible(true)
                    ->setTabOrder(5120)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
        }
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

    public function getConfigKey(): ?string
    {
        return 'sulu_page';
    }

    public function getConfig(): ?array
    {
        return [
            'teaser' => $this->teaserProviderPool->getConfiguration(),
            'versioning' => $this->versioningEnabled,
        ];
    }

    private function hasSomeWebspacePermission(): bool
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $hasWebspacePermission = $this->securityChecker->hasPermission(
                self::SECURITY_CONTEXT_PREFIX . $webspace->getKey(),
                PermissionTypes::EDIT
            );

            if ($hasWebspacePermission) {
                return true;
            }
        }

        return false;
    }
}
