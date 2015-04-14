<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class NavigationController
 * @package Sulu\Bundle\CategoryBundle\Controller
 */
class NavigationController extends FOSRestController
{
    /**
     * returns navigation for category
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoryAction(Request $request)
    {
        $view = $this->view($this->get('sulu_admin.content_navigation_collector')->getNavigationItems('category'));
        $view->setFormat('json');
        return $this->handleView($view);
    }
}
