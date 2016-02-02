<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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

        $securityTypeTitles = [];
        foreach ($securityTypes as $securityType) {
            $securityTypeTitles[] = [
                'id' => $securityType->getId(),
                'name' => $securityType->getName(),
            ];
        }

        return $this->render('SuluSecurityBundle:Template:role.form.html.twig', [
                'systems' => $systems,
                'security_types' => $securityTypeTitles,
            ]
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

    public function permissionTabFormAction()
    {
        return $this->render('SuluSecurityBundle:Template:permission-tab.form.html.twig');
    }
}
