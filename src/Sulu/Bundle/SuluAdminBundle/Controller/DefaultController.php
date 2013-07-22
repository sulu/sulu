<?php

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
