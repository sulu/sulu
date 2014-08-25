<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluSecurityRolesContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        // define navigation
        $this->setName('Roles');

        // define content-tabs
        $details = new ContentNavigationItem('content-navigation.security.details');
        $details->setAction('details');
        $details->setGroups(array('roles'));
        $details->setComponent('roles@sulusecurity');
        $details->setComponentOptions(array('display'=>'form'));

        $this->addNavigationItem($details);
    }
}
