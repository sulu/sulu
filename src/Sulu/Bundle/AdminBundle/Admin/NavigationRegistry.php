<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Symfony\Component\Translation\TranslatorInterface;

class NavigationRegistry
{
    /**
     * @var Navigation
     */
    private $navigation;

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
    public function getNavigation(): Navigation
    {
        if (!$this->navigation) {
            $this->loadNavigation();
        }

        return $this->navigation;
    }

    private function loadNavigation(): void
    {
        /** @var Navigation $navigation */
        $navigation = null;
        foreach ($this->adminPool->getAdmins() as $admin) {
            if (!$admin instanceof NavigationProviderInterface) {
                continue;
            }

            if (null === $navigation) {
                $navigation = $admin->getNavigationV2();

                continue;
            }

            $navigation = $navigation->merge($admin->getNavigationV2());
        }

        foreach ($navigation->getRoot()->getChildren() as $child) {
            $this->processNavigationItem($child);
        }

        $this->navigation = $navigation;
    }

    /**
     * Adds the translation and the child routes to the given navigation item.
     */
    private function processNavigationItem(NavigationItem $navigationItem): void
    {
        // create label from name when nothing set
        if (!$navigationItem->getLabel()) {
            $navigationItem->setLabel($this->translator->trans($navigationItem->getName(), [], 'admin_backend'));
        }

        // add child routes
        if ($navigationItem->getMainRoute()) {
            $mainPath = $this->routeRegistry->findRouteByName($navigationItem->getMainRoute())->getPath();
            foreach ($this->routeRegistry->getRoutes() as $route) {
                if (false !== strpos($route->getPath(), $mainPath)) {
                    $navigationItem->addChildRoute($route->getName());
                }
            }
        }

        // process all children
        foreach ($navigationItem->getChildren() as $child) {
            $this->processNavigationItem($child);
        }
    }
}
