<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\Authentication\RoleSettingRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoint for role-settings.
 *
 * @RouteResource("setting")
 */
class RoleSettingController extends AbstractRestController implements ClassResourceInterface
{
    /**
     * @var RoleSettingRepositoryInterface
     */
    private $roleSettingRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        RoleSettingRepositoryInterface $roleSettingRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($viewHandler);

        $this->roleSettingRepository = $roleSettingRepository;
        $this->entityManager = $entityManager;
    }

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
        $settingValue = $this->roleSettingRepository->findSettingValue($roleId, $key);

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
        $setting = $this->roleSettingRepository->findSetting($roleId, $key);
        if (!$setting) {
            $setting = $this->roleSettingRepository->createNew();
        }

        $setting->setKey($key);
        $setting->setValue($request->get('value', []));
        $setting->setRole($this->entityManager->getReference(Role::class, $roleId));

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        return $this->handleView($this->view($setting->getValue()));
    }
}
