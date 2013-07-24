<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class TranslateAdmin extends Admin {

    function __construct()
    {
        $rootNavigationItem = new NavigationItem('Root');
        $rootNavigationItem->addChild(new NavigationItem('Settings'));
        $rootNavigationItem->getChildren()[0]->addChild(new NavigationItem('Translate'));
        $this->navigation = new Navigation($rootNavigationItem);
    }
}