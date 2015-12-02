<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class WebspaceContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $general = new ContentNavigationItem('content-navigation.webspace.general');
        $general->setId('tab-general');
        $general->setAction('general');
        $general->setPosition(1);
        $general->setComponent('webspace/settings/general@sulucontent');

        $analytics = new ContentNavigationItem('content-navigation.webspace.analytics');
        $analytics->setId('tab-analytics');
        $analytics->setAction('analytics');
        $analytics->setPosition(2);
        $analytics->setComponent('webspace/settings/analytics@sulucontent');

        $urls = new ContentNavigationItem('content-navigation.webspace.urls');
        $urls->setId('tab-urls');
        $urls->setAction('urls');
        $urls->setPosition(4);
        $urls->setComponent('webspace/settings/urls@sulucontent');

        return [$general, $analytics, $urls];
    }
}
