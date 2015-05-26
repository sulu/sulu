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
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller handles all object based securities.
 */
class PermissionController implements ClassResourceInterface
{
    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

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
        SecurityCheckerInterface $securityChecker,
        RoleRepositoryInterface $roleRepository,
        ViewHandlerInterface $viewHandler
    ) {
        $this->accessControlManager = $accessControlManager;
        $this->securityChecker = $securityChecker;
        $this->roleRepository = $roleRepository;
        $this->viewHandler = $viewHandler;
    }

    public function cgetAction(Request $request)
    {
        try {
            $identifier = $request->get('id');
            $type = $request->get('type');

            if (!$identifier) {
                throw new MissingParameterException(static::class, 'id');
            }

            if (!$type) {
                throw new MissingParameterException(static::class, 'type');
            }

            $permissions = $this->accessControlManager->getPermissions($type, $identifier);

            return $this->viewHandler->handle(View::create(
                array(
                    'id' => $identifier,
                    'type' => $type,
                    'permissions' => $permissions,
                )
            ));
        } catch (RestException $exc) {
            return $this->viewHandler->handle(View::create($exc->toArray(), 400));
        }
    }

    public function postAction(Request $request)
    {
        try {
            $identifier = $request->get('id');
            $type = $request->get('type');
            $permissions = $request->get('permissions');
            $securityContext = $request->get('securityContext');

            if (!$identifier) {
                throw new MissingParameterException(static::class, 'id');
            }

            if (!$type) {
                throw new MissingParameterException(static::class, 'class');
            }

            if (!is_array($permissions)) {
                throw new RestException('The "permissions" must be passed as an array');
            }

            if ($securityContext) {
                $this->securityChecker->checkPermission($securityContext, 'security');
            }

            foreach ($permissions as $securityIdentity => $permission) {
                array_walk($permission, function (&$permissionLine) {
                    $permissionLine = $permissionLine === 'true' || $permissionLine === true;
                });

                $this->accessControlManager->setPermissions(
                    $type,
                    $identifier,
                    $securityIdentity,
                    $permission
                );
            }

            return $this->viewHandler->handle(View::create(array(
                'id' => $identifier,
                'type' => $type,
                'permissions' => $permissions,
            )));
        } catch (RestException $exc) {
            return $this->viewHandler->handle(View::create($exc->toArray(), 400));
        }
    }
}
