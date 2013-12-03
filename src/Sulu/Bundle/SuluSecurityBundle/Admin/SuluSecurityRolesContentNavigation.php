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
        $details = new NavigationItem('Details');
        $details->setAction('details');
        $details->setType('content');

        $this->addNavigationItem($details);
    }
}
