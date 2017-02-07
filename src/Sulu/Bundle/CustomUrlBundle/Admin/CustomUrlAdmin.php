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

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Includes custom-url-bundle into sulu admin.
 */
class CustomUrlAdmin extends Admin
{
    /**
     * Returns security context for custom-urls in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public static function getCustomUrlSecurityContext($webspaceKey)
    {
        return sprintf('%s%s.%s', ContentAdmin::SECURITY_SETTINGS_CONTEXT_PREFIX, $webspaceKey, 'custom-urls');
    }

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct($title, WebspaceManagerInterface $webspaceManager)
    {
        $rootNavigationItem = new NavigationItem($title);
        $this->setNavigation(new Navigation($rootNavigationItem));

        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulucustomurl';
    }

    /**
         * {@inheritdoc}
         */
    public function getSecurityContexts()
    {
        $webspaceContexts = [];
         /* @var Webspace $webspace */
         foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
             $webspaceContexts[self::getCustomUrlSecurityContext($webspace->getKey())] = [
                 PermissionTypes::VIEW,
                 PermissionTypes::ADD,
                 PermissionTypes::EDIT,
                 PermissionTypes::DELETE,
             ];
         }

        return [
             'Sulu' => [
                 'Webspace Settings' => $webspaceContexts,
             ],
         ];
    }
}
