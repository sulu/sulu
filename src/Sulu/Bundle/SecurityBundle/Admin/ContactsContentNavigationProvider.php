<?php

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
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

    public function getNavigationItems(array $options = array())
    {
        $navigation = array();

        if ($this->securityChecker->hasPermission('sulu.security.users', 'view')) {
            $permissions = new ContentNavigationItem('content-navigation.security.permissions');
            $permissions->setAction('permissions');
            $permissions->setComponent('users@sulusecurity');
            $permissions->setComponentOptions(array('display' => 'form'));
            $permissions->setDisplay(array('edit'));

            $navigation[] = $permissions;
        }

        return $navigation;
    }
}
