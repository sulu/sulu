<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

class NavigationController extends FOSRestController
{
    public function getNavigationAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');
        $navigation = $pool->getNavigation();

        $view = $this->view($navigation->toArray(), 200);

        return $this->handleView($view);
    }
}