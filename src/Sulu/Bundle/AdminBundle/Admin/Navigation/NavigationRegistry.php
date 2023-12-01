<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Navigation;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class NavigationRegistry
{
    /**
     * @var NavigationItem[]
     */
    private $navigationItems;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var ViewRegistry
     */
    private $viewRegistry;

    public function __construct(TranslatorInterface $translator, AdminPool $adminPool, ViewRegistry $viewRegistry)
    {
        $this->translator = $translator;
        $this->adminPool = $adminPool;
        $this->viewRegistry = $viewRegistry;
    }

    /**
     * @return NavigationItem[]
     */
    public function getNavigationItems(): array
    {
        if (!$this->navigationItems) {
            $this->loadNavigationItems();
        }

        return $this->navigationItems;
    }

    private function loadNavigationItems(): void
    {
        $navigationItemCollection = new NavigationItemCollection();

        $settingsNavigationItem = new NavigationItem(Admin::SETTINGS_NAVIGATION_ITEM);
        $settingsNavigationItem->setPosition(1000);
        $settingsNavigationItem->setIcon('su-cog');

        $navigationItemCollection->add($settingsNavigationItem);

        foreach ($this->adminPool->getAdmins() as $admin) {
            if (!$admin instanceof NavigationProviderInterface) {
                continue;
            }

            $admin->configureNavigationItems($navigationItemCollection);
        }

        $navigationItems = \array_filter($navigationItemCollection->all(), function($navigationItem) {
            return $navigationItem->getChildren() || $navigationItem->getView();
        });

        foreach ($navigationItems as $navigationItem) {
            $this->processNavigationItem($navigationItem);
        }

        \usort($navigationItems, function(NavigationItem $a, NavigationItem $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        $this->navigationItems = $navigationItems;
    }

    /**
     * Adds the translation and the child views to the given navigation item.
     */
    private function processNavigationItem(NavigationItem $navigationItem): void
    {
        // create label from name when no label is set
        if (!$navigationItem->getLabel()) {
            $navigationItem->setLabel($this->translator->trans($navigationItem->getName(), [], 'admin'));
        }

        // add child views
        $mainView = $navigationItem->getView();
        if ($mainView) {
            $mainPath = $this->viewRegistry->findViewByName($mainView)->getPath();
            if ('/' !== $mainPath) {
                foreach ($this->viewRegistry->getViews() as $view) {
                    if (0 === \strpos($view->getPath(), $mainPath)) {
                        $navigationItem->addChildView($view->getName());
                    }
                }
            }
        }

        // process all children
        foreach ($navigationItem->getChildren() as $child) {
            $this->processNavigationItem($child);
        }
    }
}
