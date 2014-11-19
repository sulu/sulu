<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface;

class SuluSecurityContentNavigation implements ContentNavigationInterface
{
    private $navigation = array();

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        if ($securityChecker->hasPermission('sulu.security.users', 'view')) {
            $permissions = new ContentNavigationItem('content-navigation.security.permissions');
            $permissions->setAction('permissions');
            $permissions->setComponent('permissions@sulusecurity');
            $permissions->setComponentOptions(array('display' => 'form'));
            $permissions->setDisplay(array('edit'));
            $permissions->setGroups(array('contact'));

            $this->navigation[] = $permissions;
        }
    }

    public function getNavigationItems()
    {
        return $this->navigation;
    }
}
