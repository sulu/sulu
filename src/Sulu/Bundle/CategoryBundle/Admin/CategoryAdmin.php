<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class CategoryAdmin extends Admin
{
    const DATAGRID_ROUTE = 'sulu_category.datagrid';

    const ADD_FORM_ROUTE = 'sulu_category.add_form';

    const EDIT_FORM_ROUTE = 'sulu_category.edit_form';

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;

        if (!$this->securityChecker) {
            return;
        }
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();
        $settings = $this->getNavigationItemSettings();

        if ($this->securityChecker->hasPermission('sulu.settings.categories', PermissionTypes::VIEW)) {
            $categoryItem = new NavigationItem('sulu_category.categories', $settings);
            $categoryItem->setPosition(20);
            $categoryItem->setMainRoute('sulu_category.datagrid');
        }

        if ($settings->hasChildren()) {
            $rootNavigationItem->addChild($settings);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        $locales = array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->localizationManager->getLocalizations()
            )
        );

        $formToolbarActions = [
            'sulu_admin.save',
            'sulu_admin.delete',
        ];

        return [
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::DATAGRID_ROUTE, '/categories/:locale')
                ->setResourceKey('categories')
                ->setTitle('sulu_category.categories')
                ->addDatagridAdapters(['tree_table'])
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->enableSearching()
                ->enableMoving()
                ->getRoute(),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/categories/:locale/add')
                ->setResourceKey('categories')
                ->addLocales($locales)
                ->getRoute(),
            (new Route('sulu_category.add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_category.details')
                ->addOption('formKey', 'categories')
                ->addOption('toolbarActions', $formToolbarActions)
                ->addOption('routerAttributesToFormStore', ['parentId'])
                ->addOption('backRoute', static::DATAGRID_ROUTE)
                ->addOption('editRoute', 'sulu_category.edit_form.detail')
                ->setParent(static::ADD_FORM_ROUTE),
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/categories/:locale/:id')
                ->setResourceKey('categories')
                ->addLocales($locales)
                ->getRoute(),
            (new Route('sulu_category.edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_category.details')
                ->addOption('formKey', 'categories')
                ->addOption('backRoute', static::DATAGRID_ROUTE)
                ->addOption('toolbarActions', $formToolbarActions)
                ->setParent(static::EDIT_FORM_ROUTE),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Settings' => [
                    'sulu.settings.categories' => [
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
