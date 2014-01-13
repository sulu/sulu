<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluSecurityRolesContentNavigation extends ContentNavigation
{

    public function __construct()
    {
        parent::__construct();

        // define navigation
        $this->setName('Roles');

        // define content-tabs
        $details = new NavigationItem('content-navigation.security.details');
        $details->setAction('details');
        $details->setContentType('roles');
        $details->setContentComponent('roles@sulusecurity');
        $details->setContentComponentOptions(array('display'=>'form'));

        $this->addNavigationItem($details);
    }
}
