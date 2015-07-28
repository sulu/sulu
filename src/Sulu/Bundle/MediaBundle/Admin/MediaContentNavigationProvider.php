<?php

/*
 * This file is part of the Sulu.
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
    public function getNavigationItems(array $options = [])
    {
        $files = new ContentNavigationItem('content-navigation.media.files');
        $files->setAction('files');
        $files->setComponent('collections/edit/files@sulumedia');

        $settings = new ContentNavigationItem('content-navigation.media.settings');
        $settings->setAction('settings');
        $settings->setComponent('collections/edit/settings@sulumedia');

        return [$files, $settings];
    }
}
