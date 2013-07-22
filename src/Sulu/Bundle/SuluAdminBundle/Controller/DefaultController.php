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

class DefaultController extends Controller
{
    public function indexAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');
        echo var_dump($pool);

        return $this->render('SuluAdminBundle:Default:index.html.twig', array('name' => 'SULU'));
    }
}
