<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluTranslateContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Package');

        $details = new NavigationItem('Details');
        $details->setAction('details');
        $details->setContentType('package');
        $details->setContentComponent('packages@sulutranslate');
        $details->setContentComponentOptions(array('display'=>'details'));

        $this->addNavigationItem($details);


        $settings = new NavigationItem('Settings');
        $settings->setAction('settings');
        $settings->setContentType('package');
        $settings->setContentComponent('packages@sulutranslate');
        $settings->setContentComponentOptions(array('display'=>'settings'));

        $this->addNavigationItem($settings);

    }
}
