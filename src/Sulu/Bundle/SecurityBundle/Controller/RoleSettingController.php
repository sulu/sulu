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

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\RoleSetting;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoint for role-settings.
 *
 * @RouteResource("setting")
 */
class RoleSettingController extends RestController implements ClassResourceInterface
{
    /**
     * Returns value for given role-setting.
     *
     * @param int $roleId
     * @param string $key
     *
     * @return Response
     */
    public function getAction($roleId, $key)
    {
        return $this->handleView(
            $this->view($this->get('sulu_security.role_setting_repository')->findSettingValue($roleId, $key))
        );
    }

    /**
     * Save role-setting with value from request body.
     *
     * @param Request $request
     * @param int $roleId
     * @param string $key
     *
     * @return Response
     */
    public function putAction(Request $request, $roleId, $key)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $setting = $this->get('sulu_security.role_setting_repository')->findSetting($roleId, $key);
        if (!$setting) {
            $setting = new RoleSetting();
        }

        $setting->setKey($key);
        $setting->setValue($request->get('value'));
        $setting->setRole($entityManager->getReference(Role::class, $roleId));

        $entityManager->persist($setting);
        $entityManager->flush();

        return $this->handleView($this->view($setting->getValue()));
    }
}
