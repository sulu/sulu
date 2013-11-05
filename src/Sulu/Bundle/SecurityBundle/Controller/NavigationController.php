<?php

namespace Sulu\Bundle\SecurityBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NavigationController
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class NavigationController extends Controller
{
    // has to be the same as defined in service.yml
    const SERVICE_NAME = 'sulu_security.admin.roles_navigation';

    /**
     * returns roles navigation
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rolesAction()
    {

        /** @var ContentNavigation $contentNavigation */
        if ($this->has(self::SERVICE_NAME)) {
            $contentNavigation = $this->get(self::SERVICE_NAME);
        }

        return new Response(json_encode($contentNavigation->toArray('roles')));
    }
}
