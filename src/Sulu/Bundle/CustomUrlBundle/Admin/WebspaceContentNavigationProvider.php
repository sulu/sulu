<?php

namespace Sulu\Bundle\CustomUrlBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

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
