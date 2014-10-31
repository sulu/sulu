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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluMediaContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $this->setName('Collection');

        $files = new ContentNavigationItem('content-navigation.media.files');
        $files->setAction('files');
        $files->setGroups(array('collection'));
        $files->setComponent('collections@sulumedia');
        $files->setComponentOptions(array('display'=>'files'));
        $this->addNavigationItem($files);

        $settings = new ContentNavigationItem('content-navigation.media.settings');
        $settings->setAction('settings');
        $settings->setGroups(array('collection'));
        $settings->setComponent('collections@sulumedia');
        $settings->setComponentOptions(array('display'=>'settings'));
        $this->addNavigationItem($settings);
    }
}
