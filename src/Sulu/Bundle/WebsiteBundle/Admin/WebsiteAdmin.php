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

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class WebsiteAdmin extends Admin
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        $title
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;

        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('');
        $section->setPosition(10);

        if ($this->checkLivePermissionForAllWebspaces()) {
            $settings = new NavigationItem('navigation.settings');
            $settings->setPosition(40);
            $settings->setIcon('gear');

            $cache = new NavigationItem('navigation.settings.cache', $settings);
            $cache->setPosition(50);
            $cache->setAction('settings/cache');
            $cache->setIcon('hdd-o');

            $section->addChild($settings);
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluwebsite';
    }

    /**
     * Check the permissions for all webspaces.
     * Returns true if the user has live permission in all webspaces.
     *
     * @return bool
     */
    private function checkLivePermissionForAllWebspaces()
    {
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $context = ContentAdmin::SECURITY_CONTEXT_PREFIX . $webspace->getKey();
            if (!$this->securityChecker->hasPermission($context, PermissionTypes::LIVE)) {
                return false;
            }
        }

        return true;
    }
}
