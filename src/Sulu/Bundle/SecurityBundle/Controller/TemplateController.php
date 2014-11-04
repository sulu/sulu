<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mike
 * Date: 11.09.13
 * Time: 15:57
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    public function roleFormAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');
        $contexts = $pool->getSecurityContexts();
        $systems = array_keys($contexts);

        /** @var SecurityType[] $securityTypes */
        $securityTypes = $this->getDoctrine()
            ->getRepository('Sulu\Bundle\SecurityBundle\Entity\SecurityType')
            ->findAll();

        $securityTypeTitles = array();
        foreach ($securityTypes as $securityType) {
            $securityTypeTitles[] = array(
                'id' => $securityType->getId(),
                'name' => $securityType->getName()
            );
        }

        return $this->render('SuluSecurityBundle:Template:role.form.html.twig', array(
                'systems' => $systems,
                'security_types' => $securityTypeTitles
            )
        );
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
