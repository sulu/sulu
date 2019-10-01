<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class CategoryAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.settings.categories';

    const LIST_ROUTE = 'sulu_category.list';

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
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $categoryItem = new NavigationItem('sulu_category.categories');
            $categoryItem->setPosition(20);
            $categoryItem->setMainRoute(static::LIST_ROUTE);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($categoryItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->localizationManager->getLocales();

        $formToolbarActions = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.move');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->routeBuilderFactory
                    ->createListRouteBuilder(static::LIST_ROUTE, '/categories/:locale')
                    ->setResourceKey('categories')
                    ->setListKey('categories')
                    ->setTitle('sulu_category.categories')
                    ->addListAdapters(['tree_table'])
                    ->addLocales($locales)
                    ->setDefaultLocale($locales[0])
                    ->setAddRoute(static::ADD_FORM_ROUTE)
                    ->setEditRoute(static::EDIT_FORM_ROUTE)
                    ->enableSearching()
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->routeBuilderFactory
                    ->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/categories/:locale/add')
                    ->setResourceKey('categories')
                    ->addLocales($locales)
                    ->setBackRoute(static::LIST_ROUTE)
            );
            $viewCollection->add(
                $this->routeBuilderFactory
                    ->createFormRouteBuilder('sulu_category.add_form.details', '/details')
                    ->setResourceKey('categories')
                    ->setFormKey('category_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->addRouterAttributesToFormRequest(['parentId'])
                    ->setEditRoute(static::EDIT_FORM_ROUTE)
                    ->setParent(static::ADD_FORM_ROUTE)
            );
            $viewCollection->add(
                $this->routeBuilderFactory
                    ->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/categories/:locale/:id')
                    ->setResourceKey('categories')
                    ->addLocales($locales)
                    ->setBackRoute(static::LIST_ROUTE)
                    ->addRouterAttributesToBackRoute(['id' => 'active'])
                    ->setTitleProperty('name')
            );
            $viewCollection->add(
                $this->routeBuilderFactory
                    ->createFormRouteBuilder('sulu_category.edit_form.details', '/details')
                    ->setResourceKey('categories')
                    ->setFormKey('category_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
            $viewCollection->add(
                $this->routeBuilderFactory
                    ->createFormOverlayListRouteBuilder('sulu_category.edit_form.keywords', '/keywords')
                    ->setResourceKey('category_keywords')
                    ->setListKey('category_keywords')
                    ->addListAdapters(['table'])
                    ->addRouterAttributesToListRequest(['id' => 'categoryId'])
                    ->setFormKey('category_keywords')
                    ->addRouterAttributesToFormRequest(['id' => 'categoryId'])
                    ->setTabTitle('sulu_category.keywords')
                    ->addToolbarActions([new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')])
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Settings' => [
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
