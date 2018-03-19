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
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Provides custom-url-tab for webspace settings.
 */
class WebspaceContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(SecurityCheckerInterface $securityChecker, WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->securityChecker = $securityChecker;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $navigationItems = [];

        if (!$options['webspace']) {
            return [];
        }

        $webspace = $this->webspaceManager->findWebspaceByKey($options['webspace']);
        $customUrls = $webspace->getPortals()[0]->getEnvironment($this->environment)->getCustomUrls();

        if (count($customUrls)
            && $this->securityChecker->hasPermission(
                CustomUrlAdmin::getCustomUrlSecurityContext($options['webspace']),
                PermissionTypes::VIEW
            )
        ) {
            $customUrlNavigationItem = new ContentNavigationItem('content-navigation.webspace.custom-url');
            $customUrlNavigationItem->setId('tab-custom-urls');
            $customUrlNavigationItem->setAction('custom-urls');
            $customUrlNavigationItem->setPosition(40);
            $customUrlNavigationItem->setComponent('webspace/settings/custom-url@sulucustomurl');

            $navigationItems[] = $customUrlNavigationItem;
        }

        return $navigationItems;
    }
}
