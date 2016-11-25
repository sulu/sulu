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
use Sulu\Bundle\ContentBundle\Document\PageDocument;

/**
 * Provides automation tab for pages.
 */
class ContentAutomationContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $automation = new ContentNavigationItem('sulu_automation.automation');
        $automation->setId('tab-automation');
        $automation->setPosition(45);
        $automation->setAction('automation');
        $automation->setComponent('automation-tab@suluautomation');
        $automation->setComponentOptions(['entityClass' => PageDocument::class]);
        $automation->setDisplay(['edit']);

        return [$automation];
    }
}
