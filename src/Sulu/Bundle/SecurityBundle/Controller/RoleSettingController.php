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
use Sulu\Bundle\SecurityBundle\Entity\RoleSettingRepository;
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
        $settingValue = $this->get('sulu.repository.role_setting')->findSettingValue($roleId, $key);

        return $this->handleView($this->view($settingValue));
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
        /** @var RoleSettingRepository $repository */
        $repository = $this->get('sulu.repository.role_setting');

        $setting = $repository->findSetting($roleId, $key);
        if (!$setting) {
            $setting = $repository->createNew();
        }

        $setting->setKey($key);
        $setting->setValue($request->get('value', []));
        $setting->setRole($entityManager->getReference(Role::class, $roleId));

        $entityManager->persist($setting);
        $entityManager->flush();

        return $this->handleView($this->view($setting->getValue()));
    }
}
