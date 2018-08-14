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
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class CategoryAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManager
     */
    private $localizationManager;

    public function __construct(
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager
    ) {
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
            (new Route('sulu_category.datagrid', '/categories/:locale', 'sulu_admin.datagrid'))
                ->addOption('locales', $locales)
                ->addAttributeDefault('locale', $locales[0])
                ->addOption('title', 'sulu_category.categories')
                ->addOption('resourceKey', 'categories')
                ->addOption('adapters', ['tree_table'])
                ->addOption('addRoute', 'sulu_category.add_form.detail')
                ->addOption('editRoute', 'sulu_category.edit_form.detail')
                ->addOption('searchable', false),
            (new Route('sulu_category.add_form', '/categories/:locale/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'categories')
                ->addOption('toolbarActions', $formToolbarActions)
                ->addOption('routerAttributesToFormStore', ['parentId'])
                ->addOption('locales', $locales),
            (new Route('sulu_category.add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_category.details')
                ->addOption('backRoute', 'sulu_category.datagrid')
                ->addOption('editRoute', 'sulu_category.edit_form.detail')
                ->setParent('sulu_category.add_form'),
            (new Route('sulu_category.edit_form', '/categories/:locale/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'categories')
                ->addOption('toolbarActions', $formToolbarActions)
                ->addOption('locales', $locales),
            (new Route('sulu_category.edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_category.details')
                ->addOption('backRoute', 'sulu_category.datagrid')
                ->setParent('sulu_category.edit_form'),
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
