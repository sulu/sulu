<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller handles all object based securities
 */
class PermissionController implements ClassResourceInterface
{
    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    public function __construct(
        AccessControlManagerInterface $accessControlManager,
        RoleRepositoryInterface $roleRepository,
        ViewHandlerInterface $viewHandler
    ) {
        $this->accessControlManager = $accessControlManager;
        $this->roleRepository = $roleRepository;
        $this->viewHandler = $viewHandler;
    }

    public function postAction(Request $request)
    {
        try {
            $objectIdentifier = $request->get('id');
            $class = $request->get('class');
            $permissions = $request->get('permissions');

            if (!$objectIdentifier) {
                throw new MissingParameterException(static::class, 'id');
            }

            if (!$class) {
                throw new MissingParameterException(static::class, 'class');
            }

            if (!is_array($permissions)) {
                throw new RestException('The "permissions" must be passed as an array');
            }

            foreach ($permissions as $permission) {
                array_walk($permission['permissions'], function (&$permissionLine) {
                    $permissionLine = $permissionLine === 'true' || $permissionLine === true;
                });

                $this->accessControlManager->setPermissions(
                    $class,
                    $objectIdentifier,
                    $this->roleRepository->findRoleById($permission['role']['id']),
                    $permission['permissions']
                );
            }

            return $this->viewHandler->handle(View::create(array(
                'id' => $objectIdentifier,
                'class' => $class,
                'permissions' => $permissions
            )));
        } catch (RestException $exc) {
            return $this->viewHandler->handle(View::create($exc->toArray(), 400));
        }
    }
}
