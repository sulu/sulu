<?php

namespace Sulu\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('SuluAdminBundle:Default:index.html.twig', array('name' => var_dump($this->get('sulu_admin.admin_pool'))));
    }
}
