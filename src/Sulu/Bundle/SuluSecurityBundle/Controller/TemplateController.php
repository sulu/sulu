<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mike
 * Date: 11.09.13
 * Time: 15:57
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{

    public function roleFormAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');
        $contexts = $pool->getSecurityContexts();
        $systems = array_keys($contexts);

        return $this->render('SuluSecurityBundle:Template:role.form.html.twig', array('systems' => $systems));
    }

    public function permissionformAction()
    {
        return $this->render('SuluSecurityBundle:Template:permission.form.html.twig');
    }

    public function roleListAction()
    {
        return $this->render('SuluSecurityBundle:Template:role.list.html.twig');
    }
}
