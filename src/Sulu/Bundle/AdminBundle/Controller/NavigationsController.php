<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

/**
 * This controller is responsible for delivering the first static part of the navigation.
 * Therefore it uses the Navigation combined by the admin pool.
 */
class NavigationsController extends FOSRestController
{
    public function getNavigationAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');
        $navigation = $pool->getNavigation();

        $view = $this->view($navigation->toArray(), 200);

        return $this->handleView($view);
    }
}
