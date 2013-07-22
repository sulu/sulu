<?php

namespace Sulu\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');
        foreach ($pool->getAdmins() as $admin) {
            echo var_dump($this->get($admin));
        }
        return $this->render('SuluAdminBundle:Default:index.html.twig', array('name' => 'SULU'));
    }
}
