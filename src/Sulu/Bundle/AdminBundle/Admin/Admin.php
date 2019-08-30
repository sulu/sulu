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

use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

/**
 * Defines all the required information from a bundle's admin class.
 */
abstract class Admin implements RouteProviderInterface, NavigationProviderInterface
{
    public function getNavigationItemRoot(): NavigationItem
    {
        $root = new NavigationItem('root');

        return $root;
    }

    public function getNavigationItemSettings(): NavigationItem
    {
        $settings = new NavigationItem('sulu_admin.settings');
        $settings->setPosition(50);
        $settings->setIcon('su-cog');

        return $settings;
    }

    /**
     * Returns all the routes for the frontend admin interface.
     *
     * @return Route[]
     */
    public function configureRoutes(RouteCollection $routeCollection): void
    {
    }

    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     *
     * @return array
     */
    public function getSecurityContexts()
    {
        return [];
    }

    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     *
     * @return array
     */
    public function getSecurityContextsWithPlaceholder()
    {
        return $this->getSecurityContexts();
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = new NavigationItem('root');

        return new Navigation($rootNavigationItem);
    }

    public function getConfig(): ?array
    {
        return null;
    }

    public function getConfigKey(): ?string
    {
        return null;
    }
}
