<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class WebsiteAdmin extends Admin
{
    public function __construct($title)
    {
        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('');
        $section->setPosition(10);

        $settings = new NavigationItem('navigation.settings');
        $settings->setPosition(40);
        $settings->setIcon('gear');

        $cache = new NavigationItem('navigation.settings.cache', $settings);
        $cache->setPosition(50);
        $cache->setAction('settings/cache');
        $cache->setIcon('hdd-o');

        $section->addChild($settings);
        $rootNavigationItem->addChild($section);
        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluwebsite';
    }
}
