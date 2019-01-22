<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Admin for snippet.
 */
class SnippetAdmin extends Admin
{
    const DATAGRID_ROUTE = 'sulu_snippet.datagrid';

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

        if ($this->securityChecker->hasPermission('sulu.global.snippets', 'view')) {
            $snippet = new NavigationItem('sulu_snippet.snippets');
            $snippet->setPosition(20);
            $snippet->setIcon('su-snippet');
            $snippet->setAction('snippet/snippets');
            $snippet->setMainRoute(static::DATAGRID_ROUTE);

            $rootNavigationItem->addChild($snippet);
        }

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        $snippetLocales = array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->webspaceManager->getAllLocalizations()
            )
        );

        $formToolbarActions = [
            'sulu_admin.save',
            'sulu_admin.type',
            'sulu_admin.delete',
        ];

        return [
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::DATAGRID_ROUTE, '/snippets/:locale')
                ->setResourceKey('snippets')
                ->setDatagridKey('snippets')
                ->setTitle('sulu_snippet.snippets')
                ->addDatagridAdapters(['table'])
                ->addLocales($snippetLocales)
                ->setDefaultLocale($snippetLocales[0])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/snippets/:locale/add')
                ->setResourceKey('snippets')
                ->addLocales($snippetLocales)
                ->setBackRoute(static::DATAGRID_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_snippet.add_form.details', '/details')
                ->setResourceKey('snippets')
                ->setFormKey('snippet')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::ADD_FORM_ROUTE)
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/snippets/:locale/:id')
                ->setResourceKey('snippets')
                ->addLocales($snippetLocales)
                ->setBackRoute(static::DATAGRID_ROUTE)
                ->setTitleProperty('title')
                ->getRoute(),
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_snippet.edit_form.details', '/details')
                ->setResourceKey('snippets')
                ->setFormKey('snippet')
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE)
                ->getRoute(),
        ];
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
                    'sulu.global.snippets' => [
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
