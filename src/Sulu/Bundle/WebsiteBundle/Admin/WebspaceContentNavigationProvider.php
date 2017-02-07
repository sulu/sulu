<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Provides tabs for webspace settings.
 */
class WebspaceContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        if (!$this->securityChecker->hasPermission(
            WebsiteAdmin::getAnalyticsSecurityContext($options['webspace']),
            PermissionTypes::VIEW
        )
        ) {
            return [];
        }

        $analytics = new ContentNavigationItem('content-navigation.webspace.analytics');
        $analytics->setId('tab-analytics');
        $analytics->setAction('analytics');
        $analytics->setPosition(20);
        $analytics->setComponent('webspace/settings/analytics@suluwebsite');

        return [$analytics];
    }
}
