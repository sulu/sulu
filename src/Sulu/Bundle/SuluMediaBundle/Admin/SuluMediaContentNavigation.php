<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluMediaContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        $this->setName('Collection');

        $files = new NavigationItem('content-navigation.media.files');
        $files->setAction('files');
        $files->setContentType('collection');
        $files->setContentComponent('collections@sulumedia');
        $files->setContentComponentOptions(array('display'=>'files'));
        $this->addNavigationItem($files);

        $settings = new NavigationItem('content-navigation.media.settings');
        $settings->setAction('settings');
        $settings->setContentType('collection');
        $settings->setContentComponent('collections@sulumedia');
        $settings->setContentComponentOptions(array('display'=>'settings'));
        $this->addNavigationItem($settings);
    }
}
