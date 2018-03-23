<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluSearchAdmin extends Admin
{
    public function getNavigation(): Navigation
    {
        $rootNavigationItem = new NavigationItem('root');

        $section = new NavigationItem('navigation.search-section');
        $section->setPosition(1);

        $rootNavigationItem->addChild($section);

        $search = new NavigationItem('navigation.search');
        $search->setPosition(10);
        $search->setIcon('search');
        $search->setEvent('search');

        $section->addChild($search);

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulusearch';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [];
    }
}
