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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluTranslateContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $this->setName('Package');

        $details = new ContentNavigationItem('Details');
        $details->setAction('details');
        $details->setGroups(array('package'));
        $details->setComponent('packages@sulutranslate');
        $details->setComponentOptions(array('display' => 'details'));
        $details->setDisplay(array('edit'));

        $this->addNavigationItem($details);

        $settings = new ContentNavigationItem('Settings');
        $settings->setAction('settings');
        $settings->setGroups(array('package'));
        $settings->setComponent('packages@sulutranslate');
        $settings->setComponentOptions(array('display' => 'settings'));

        $this->addNavigationItem($settings);
    }
}
