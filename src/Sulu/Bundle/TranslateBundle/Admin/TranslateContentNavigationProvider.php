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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class TranslateContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = array())
    {
        $details = new ContentNavigationItem('Details');
        $details->setAction('details');
        $details->setComponent('packages@sulutranslate');
        $details->setComponentOptions(array('display' => 'details'));
        $details->setDisplay(array('edit'));

        $settings = new ContentNavigationItem('Settings');
        $settings->setAction('settings');
        $settings->setComponent('packages@sulutranslate');
        $settings->setComponentOptions(array('display' => 'settings'));

        return array($details, $settings);
    }
}
