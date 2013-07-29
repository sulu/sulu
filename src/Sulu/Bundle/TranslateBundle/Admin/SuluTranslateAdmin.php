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

class SuluTranslateAdmin extends Admin {

    function __construct()
    {
        $rootNavigationItem = new NavigationItem('Root');
        $settings = new NavigationItem('Settings');
        $settings->setIcon('settings');
        $rootNavigationItem->addChild($settings);
        $translate = new NavigationItem('Translate');
        $translate->setAction('settings/translate');
        $settings->addChild($translate);
        $this->navigation = new Navigation($rootNavigationItem);
    }
}