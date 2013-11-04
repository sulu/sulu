<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class SuluSecurityContentNavigation implements ContentNavigationInterface
{

    private $navigation = array();

    public function __construct()
    {
        $permissions = new NavigationItem('Permissions');
        $permissions->setContentType('contact');
        $permissions->setAction('permissions');
        $permissions->setContentDisplay(array('edit'));
        $permissions->setType('content');

        $this->navigation[] = $permissions;
    }

    public function getNavigationItems()
    {
        return $this->navigation;
    }
}
