<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class TagAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.settings.tags';

    const LIST_ROUTE = 'sulu_tag.list';

    const ADD_FORM_ROUTE = 'sulu_tag.add_form';

    const EDIT_FORM_ROUTE = 'sulu_tag.edit_form';

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

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $tags = new NavigationItem('sulu_tag.tags');
            $tags->setPosition(30);
            $tags->setMainRoute(static::LIST_ROUTE);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($tags);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $formToolbarActions = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/tags')
                    ->setResourceKey('tags')
                    ->setListKey('tags')
                    ->setTitle('sulu_tag.tags')
                    ->addListAdapters(['table'])
                    ->setAddRoute(static::ADD_FORM_ROUTE)
                    ->setEditRoute(static::EDIT_FORM_ROUTE)
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/tags/add')
                    ->setResourceKey('tags')
                    ->setBackRoute(static::LIST_ROUTE)
            );
            $viewCollection->add(
                $this->routeBuilderFactory->createFormRouteBuilder('sulu_tag.add_form.details', '/details')
                    ->setResourceKey('tags')
                    ->setFormKey('tag_details')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditRoute(static::EDIT_FORM_ROUTE)
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::ADD_FORM_ROUTE)
            );
            $viewCollection->add(
                $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/tags/:id')
                    ->setResourceKey('tags')
                    ->setBackRoute(static::LIST_ROUTE)
                    ->setTitleProperty('name')
            );
            $viewCollection->add(
                $this->routeBuilderFactory->createFormRouteBuilder('sulu_tag.edit_form.details', '/details')
                    ->setResourceKey('tags')
                    ->setFormKey('tag_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
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
