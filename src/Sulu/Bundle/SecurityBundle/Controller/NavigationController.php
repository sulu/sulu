<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        if ($this->has(static::SERVICE_NAME)) {
            $contentNavigation = $this->get(static::SERVICE_NAME);
        }

        return new JsonResponse(json_encode($contentNavigation->toArray('roles')));
    }
}
