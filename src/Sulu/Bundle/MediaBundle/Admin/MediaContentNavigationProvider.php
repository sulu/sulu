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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class MediaContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = array())
    {
        $files = new ContentNavigationItem('content-navigation.media.files');
        $files->setAction('files');
        $files->setComponent('collections@sulumedia');
        $files->setComponentOptions(array('display'=>'files'));

        $settings = new ContentNavigationItem('content-navigation.media.settings');
        $settings->setAction('settings');
        $settings->setComponent('collections@sulumedia');
        $settings->setComponentOptions(array('display'=>'settings'));

        return array($files, $settings);
    }
}
