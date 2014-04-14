<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluSecurityContentNavigation implements ContentNavigationInterface
{

    private $navigation = array();

    public function __construct()
    {
        $permissions = new NavigationItem('content-navigation.security.permissions');
        $permissions->setAction('permissions');

        $permissions->setContentComponent('permissions@sulusecurity');
        $permissions->setContentComponentOptions(array('display'=>'form'));
        $permissions->setContentDisplay(array('edit'));
        $permissions->setContentType('contact');

        $this->navigation[] = $permissions;
    }

    public function getNavigationItems()
    {
        return $this->navigation;
    }
}
