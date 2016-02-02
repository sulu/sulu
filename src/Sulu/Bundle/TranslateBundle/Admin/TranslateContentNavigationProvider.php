<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class TranslateContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('Details');
        $details->setAction('details');
        $details->setPosition(10);
        $details->setComponent('packages@sulutranslate');
        $details->setComponentOptions(['display' => 'details']);
        $details->setDisplay(['edit']);

        $settings = new ContentNavigationItem('Settings');
        $settings->setAction('settings');
        $settings->setPosition(20);
        $settings->setComponent('packages@sulutranslate');
        $settings->setComponentOptions(['display' => 'settings']);

        return [$details, $settings];
    }
}
