<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Controller\PermissionController;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;

class PermissionControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionController
     */
    private $permissionController;

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

    public function setUp()
    {
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);

        $this->permissionController = new PermissionController(
            $this->accessControlManager->reveal(),
            $this->securityChecker->reveal(),
            $this->roleRepository->reveal(),
            $this->viewHandler->reveal()
        );
    }

    public function providePermissionData()
    {
        return [
            [
                '1',
                'Acme\Example',
                [
                    'add' => 'true',
                    'view' => true,
                    'delete' => false,
                    'edit' => 'false',
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providePermissionData
     */
    public function testGetAction($id, $class, $permissions)
    {
        $request = new Request(['id' => $id, 'type' => $class]);
        $this->accessControlManager->getPermissions($class, $id)->willReturn([1 => $permissions]);

        $this->viewHandler->handle(
            View::create(
                [
                    'id' => $id,
                    'type' => $class,
                    'permissions' => [1 => $permissions],
                ]
            )
        )->shouldBeCalled();

        $this->permissionController->cgetAction($request);
    }

    /**
     * @dataProvider providePermissionData
     */
    public function testPostAction($id, $class, $permissions)
    {
        $request = new Request(
            [],
            [
                'id' => $id,
                'type' => $class,
                'permissions' => [
                    1 => $permissions,
                ],
            ]
        );

        array_walk(
            $permissions,
            function (&$permissionLine) {
                $permissionLine = $permissionLine === 'true' || $permissionLine === true;
            }
        );

        $this->viewHandler->handle(
            View::create(
                [
                    'id' => $id,
                    'type' => $class,
                    'permissions' => [
                        1 => $permissions,
                    ],
                ]
            )
        )->shouldBeCalled();

        $this->permissionController->postAction($request);
    }

    public function provideWrongPermissionData()
    {
        return [
            [null, null, null],
            ['1', null, []],
            [null, 'Acme\Example', []],
            ['1', 'Acme\Example', null],
        ];
    }

    /**
     * @dataProvider provideWrongPermissionData
     */
    public function testPostActionWithWrongData($id, $class, $permissions)
    {
        $request = new Request(
            [],
            [
                'id' => $id,
                'type' => $class,
                'permissions' => $permissions,
            ]
        );

        $this->accessControlManager->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $this->permissionController->postAction($request);
    }
}
