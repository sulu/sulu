<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use FOS\RestBundle\View\View;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;

class RestControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sulu\Component\Rest\RestController
     */
    protected $controller;

    public function setUp()
    {
        $this->controller = $this->getMockForAbstractClass(RestController::class);
    }

    public function testResponseGetById()
    {
        $method = new \ReflectionMethod(RestController::class, 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function($id) {
            return ['id' => $id];
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(200, $view->getStatusCode());
        $this->assertEquals(['id' => 1], $view->getData());
    }

    public function testResponseGetByNotExistingId()
    {
        $method = new \ReflectionMethod(RestController::class, 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function($id) {
            return;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testProcessPutEmpty()
    {
        $delete = function() {
            $this->fail('delete should not be called');
        };

        $update = function() {
            $this->fail('update should not be called');
        };

        $add = function() {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(RestController::class, 'processPut');
        $method->setAccessible(true);

        $method->invoke($this->controller, [], [], $delete, $update, $add);
    }

    public function testProcessPutWithDelete()
    {
        $deleteCalled = false;
        $delete = function() use (&$deleteCalled) {
            $deleteCalled = true;
        };

        $update = function() {
            $this->fail('update should not be called');
        };

        $add = function() {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(RestController::class, 'processPut');
        $method->setAccessible(true);

        $object = $this->prophesize(ApiEntity::class);
        $object->getId()->willReturn(1);

        $method->invoke(
            $this->controller,
            [
                $object->reveal(),
            ],
            [],
            $delete,
            $update,
            $add
        );

        $this->assertTrue($deleteCalled);
    }

    public function testProcessPutWithUpdate()
    {
        $delete = function() {
            $this->fail('delete should not be called');
        };

        $updateCalled = false;
        $update = function() use (&$updateCalled) {
            $updateCalled = true;
        };

        $add = function() {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(RestController::class, 'processPut');
        $method->setAccessible(true);

        $object = $this->prophesize(ApiEntity::class);
        $object->getId()->willReturn(1);

        $method->invoke(
            $this->controller,
            [
                $object->reveal(),
            ],
            [
                [
                    'id' => 1,
                ],
            ],
            $delete,
            $update,
            $add
        );

        $this->assertTrue($updateCalled);
    }

    public function testProcessPutWithAdd()
    {
        $delete = function() {
            $this->fail('delete should not be called');
        };

        $update = function() {
            $this->fail('update shoudl not be called');
        };

        $addCalled = false;
        $add = function() use (&$addCalled) {
            $addCalled = true;
        };

        $method = new \ReflectionMethod(RestController::class, 'processPut');
        $method->setAccessible(true);

        $method->invoke(
            $this->controller,
            [],
            [
                [
                    'id' => 1,
                ],
            ],
            $delete,
            $update,
            $add
        );

        $this->assertTrue($addCalled);
    }

    public function testDelete()
    {
        $method = new \ReflectionMethod(RestController::class, 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function($id) {
            return true;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(204, $view->getStatusCode());
        $this->assertEquals(null, $view->getData());
    }

    public function testDeleteWithNotExistingEntity()
    {
        $method = new \ReflectionMethod(RestController::class, 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function($id) {
            throw new EntityNotFoundException('SuluCoreBundle:Example', 7);
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testDeleteWithError()
    {
        $method = new \ReflectionMethod(RestController::class, 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function($id) {
            throw new RestException();
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(400, $view->getStatusCode());
    }
}
