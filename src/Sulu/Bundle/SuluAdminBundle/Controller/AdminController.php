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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * This controller is responsible for the administration part of Sulu.
 * Therefore it bootstraps a backbone-application.
 * @package Sulu\Bundle\AdminBundle\Controller
 */
class AdminController extends Controller
{
    public function indexAction()
    {
        return $this->render('SuluAdminBundle:Admin:index.html.twig', array());
    }
}
