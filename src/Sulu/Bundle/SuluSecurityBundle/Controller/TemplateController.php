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

class TemplateController extends Controller {

    public function rolelistAction()
    {
        return $this->render('SuluSecurityBundle:Template:role.list.html.twig', array());
    }
}
