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
     * The navigation describes the position of the admin.
     *
     * @var Navigation
     */
    protected $navigation;

    /**
     * Returns all the routes for the frontend admin interface.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return [];
    }

    /**
     * Sets the navigation containing the position of the admin in the navigation.
     *
     * @param \Sulu\Bundle\AdminBundle\Navigation\Navigation $navigation
     */
    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    /**
     * Returns a navigation containing the position of the admin in the navigation.
     *
     * @return \Sulu\Bundle\AdminBundle\Navigation\Navigation
     */
    public function getNavigation(): Navigation
    {
        return $this->navigation;
    }

    /**
     * Returns the bundle name for the javascript main file.
     *
     * @deprecated Will not be used and removed in 2.0
     *
     * @return string
     */
    public function getJsBundleName()
    {
        return;
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

    public function getNavigationV2(): Navigation
    {
        $rootNavigationItem = new NavigationItem('root');

        return new Navigation($rootNavigationItem);
    }
}
