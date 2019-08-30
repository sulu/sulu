<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Symfony\Component\Translation\TranslatorInterface;

class NavigationRegistry
{
    /**
     * @var NavigationItem
     */
    private $navigationItem;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var RouteRegistry
     */
    private $routeRegistry;

    public function __construct(TranslatorInterface $translator, AdminPool $adminPool, RouteRegistry $routeRegistry)
    {
        $this->translator = $translator;
        $this->adminPool = $adminPool;
        $this->routeRegistry = $routeRegistry;
    }

    /**
     * Returns the navigation combined from all Admin objects.
     */
    public function getNavigation(): NavigationItem
    {
        if (!$this->navigationItem) {
            $this->loadNavigationItems();
        }

        return $this->navigationItem;
    }

    private function loadNavigationItems(): void
    {
        /** @var NavigationItem $navigation */
        $navigationItem = null;
        foreach ($this->adminPool->getAdmins() as $admin) {
            if (!$admin instanceof NavigationProviderInterface) {
                continue;
            }

            if (null === $navigationItem) {
                $navigationItem = $admin->getNavigation();

                continue;
            }

            $navigationItem = $navigationItem->merge($admin->getNavigation());
        }

        foreach ($navigationItem->getChildren() as $child) {
            $this->processNavigationItem($child);
        }

        $this->navigationItem = $navigationItem;
    }

    /**
     * Adds the translation and the child routes to the given navigation item.
     */
    private function processNavigationItem(NavigationItem $navigationItem): void
    {
        // create label from name when no label is set
        if (!$navigationItem->getLabel()) {
            $navigationItem->setLabel($this->translator->trans($navigationItem->getName(), [], 'admin'));
        }

        // add child routes
        $mainRoute = $navigationItem->getMainRoute();
        if ($mainRoute) {
            $mainPath = $this->routeRegistry->findRouteByName($mainRoute)->getPath();
            if ('/' !== $mainPath) {
                foreach ($this->routeRegistry->getRoutes() as $route) {
                    if (0 === strpos($route->getPath(), $mainPath)) {
                        $navigationItem->addChildRoute($route->getName());
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
