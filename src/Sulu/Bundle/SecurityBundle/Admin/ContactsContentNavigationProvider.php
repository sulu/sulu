<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ContactsContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    public function getNavigationItems(array $options = [])
    {
        $navigation = [];

        if ($this->securityChecker->hasPermission('sulu.security.users', PermissionTypes::VIEW)) {
            $permissions = new ContentNavigationItem('content-navigation.security.permissions');
            $permissions->setAction('permissions');
            $permissions->setPosition(30);
            $permissions->setComponent('users@sulusecurity');
            $permissions->setDisplay(['edit']);

            $navigation[] = $permissions;
        }

        return $navigation;
    }
}
