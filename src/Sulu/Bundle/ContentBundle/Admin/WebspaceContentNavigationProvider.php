<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

/**
 * Provides tabs for webspace settings.
 */
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
        $general->setPosition(10);
        $general->setComponent('webspace/settings/general@sulucontent');

        return [$general];
    }
}
