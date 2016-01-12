<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

/**
 * Provides custom-url-tab for webspace settings.
 */
class WebspaceContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $contentNavigationItem = new ContentNavigationItem('content-navigation.webspace.custom-url');
        $contentNavigationItem->setId('tab-custom-urls');
        $contentNavigationItem->setAction('custom-urls');
        $contentNavigationItem->setPosition(40);
        $contentNavigationItem->setComponent('webspace/settings/custom-url@sulucustomurl');

        return [$contentNavigationItem];
    }
}
