<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class TagAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        $settings = Admin::getNavigationItemSettings();

        if ($this->securityChecker->hasPermission('sulu.settings.tags', 'view')) {
            $roles = new NavigationItem('sulu_tag.tags', $settings);
            $roles->setPosition(30);
            $roles->setMainRoute('sulu_tag.datagrid');
        }

        if ($settings->hasChildren()) {
            $rootNavigationItem->addChild($settings);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        $formToolbarActions = [
            'sulu_admin.save',
            'sulu_admin.delete',
        ];

        return [
            (new Route('sulu_tag.datagrid', '/tags', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_tag.tags')
                ->addOption('resourceKey', 'tags')
                ->addOption('adapters', ['table'])
                ->addOption('addRoute', 'sulu_tag.add_form.detail')
                ->addOption('editRoute', 'sulu_tag.edit_form.detail'),
            (new Route('sulu_tag.add_form', '/tags/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'tags')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_tag.add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_tag.details')
                ->addOption('formKey', 'tags')
                ->addOption('backRoute', 'sulu_tag.datagrid')
                ->addOption('editRoute', 'sulu_tag.edit_form.detail')
                ->setParent('sulu_tag.add_form'),
            (new Route('sulu_tag.edit_form', '/tags/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'tags')
                ->addOption('toolbarActions', $formToolbarActions),
            (new Route('sulu_tag.edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_tag.details')
                ->addOption('formKey', 'tags')
                ->addOption('backRoute', 'sulu_tag.datagrid')
                ->setParent('sulu_tag.edit_form'),
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
                    'sulu.settings.tags' => [
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
