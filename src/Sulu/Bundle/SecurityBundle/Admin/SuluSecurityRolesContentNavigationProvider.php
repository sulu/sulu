<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class SuluSecurityRolesContentNavigationProvider implements ContentNavigationProviderInterface
{
    public function getNavigationItems(array $options = array())
    {
        $details = new ContentNavigationItem('content-navigation.security.details');
        $details->setAction('details');
        $details->setGroups(array('roles'));
        $details->setComponent('roles@sulusecurity');
        $details->setComponentOptions(array('display'=>'form'));

        return array($details);
    }
}
