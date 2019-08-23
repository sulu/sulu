<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Admin for snippet.
 */
class SnippetAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.global.snippets';

    const LIST_ROUTE = 'sulu_snippet.list';

    const ADD_FORM_ROUTE = 'sulu_snippet.add_form';

    const EDIT_FORM_ROUTE = 'sulu_snippet.edit_form';

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
     * @var bool
     */
    private $defaultEnabled;

    /**
     * Returns security context for default-snippets in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public static function getDefaultSnippetsSecurityContext($webspaceKey)
    {
        return sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'default-snippets');
    }

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        WebspaceManagerInterface $webspaceManager,
        $defaultEnabled
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->webspaceManager = $webspaceManager;
        $this->defaultEnabled = $defaultEnabled;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $snippet = new NavigationItem('sulu_snippet.snippets');
            $snippet->setPosition(20);
            $snippet->setIcon('su-snippet');
            $snippet->setMainRoute(static::LIST_ROUTE);

            $rootNavigationItem->addChild($snippet);
        }

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        $snippetLocales = $this->webspaceManager->getAllLocales();

        $formToolbarActionsWithType = [];
        $formToolbarActionsWithoutType = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = 'sulu_admin.add';
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActionsWithoutType[] = 'sulu_admin.save';
            $formToolbarActionsWithType[] = 'sulu_admin.save';
            $formToolbarActionsWithType[] = 'sulu_admin.type';
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActionsWithType[] = 'sulu_admin.delete';
            $listToolbarActions[] = 'sulu_admin.delete';
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = 'sulu_admin.export';
        }

        $routes = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $routes[] = $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/snippets/:locale')
                ->setResourceKey('snippets')
                ->setListKey('snippets')
                ->setTitle('sulu_snippet.snippets')
                ->addListAdapters(['table'])
                ->addLocales($snippetLocales)
                ->setDefaultLocale($snippetLocales[0])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/snippets/:locale/add')
                ->setResourceKey('snippets')
                ->addLocales($snippetLocales)
                ->setBackRoute(static::LIST_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory->createFormRouteBuilder('sulu_snippet.add_form.details', '/details')
                ->setResourceKey('snippets')
                ->setFormKey('snippet')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActionsWithType)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/snippets/:locale/:id')
                ->setResourceKey('snippets')
                ->addLocales($snippetLocales)
                ->setBackRoute(static::LIST_ROUTE)
                ->setTitleProperty('title')
                ->getRoute();
            $routes[] = $this->routeBuilderFactory->createFormRouteBuilder('sulu_snippet.edit_form.details', '/details')
                ->setResourceKey('snippets')
                ->setFormKey('snippet')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActionsWithType)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute();
            $routes[] = $this->routeBuilderFactory
                ->createFormRouteBuilder('sulu_snippet.edit_form.taxonomies', '/taxonomies')
                ->setResourceKey('snippets')
                ->setFormKey('snippet_taxonomies')
                ->setTabTitle('sulu_snippet.taxonomies')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setTitleVisible(true)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute();
            $routes[] = (new Route('sulu_snippet.snippet_areas', '/snippet-areas', 'sulu_snippet.snippet_areas'))
                ->setOption('tabTitle', 'sulu_snippet.default_snippets')
                ->setOption('tabOrder', 3072)
                ->setParent(PageAdmin::WEBSPACE_TABS_ROUTE)
                ->addRerenderAttribute('webspace');
        }

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $contexts = $this->getGlobalSnippetsSecurityContext();

        if ($this->defaultEnabled) {
            $webspaceContexts = [];
            /* @var Webspace $webspace */
            foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
                $webspaceContexts[self::getDefaultSnippetsSecurityContext($webspace->getKey())] = [
                    PermissionTypes::VIEW,
                    PermissionTypes::EDIT,
                ];
            }

            $contexts['Sulu']['Webspaces'] = $webspaceContexts;
        }

        return $contexts;
    }

    public function getSecurityContextsWithPlaceholder()
    {
        $contexts = $this->getGlobalSnippetsSecurityContext();

        if ($this->defaultEnabled) {
            $webspaceContexts[self::getDefaultSnippetsSecurityContext('#webspace#')] = [
                PermissionTypes::VIEW,
                PermissionTypes::EDIT,
            ];

            $contexts['Sulu']['Webspaces'] = $webspaceContexts;
        }

        return $contexts;
    }

    private function getGlobalSnippetsSecurityContext()
    {
        return [
            'Sulu' => [
                'Global' => [
                    static::SECURITY_CONTEXT => [
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
