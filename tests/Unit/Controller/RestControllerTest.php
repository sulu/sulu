<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\Repository;

use FOS\RestBundle\View\View;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;

class RestControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sulu\Component\Rest\RestController
     */
    protected $controller;

    protected $mockedObject;

    public function setUp()
    {
        $this->controller = $this->getMockForAbstractClass('\Sulu\Component\Rest\RestController');
        $this->mockedObject = $this->getMock('stdClass', array('getId'));
        $this->mockedObject->expects($this->any())->method('getId')->will($this->returnValue(1));
    }

    public function testResponseGetById()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function ($id) {
            return array('id' => $id);
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(200, $view->getStatusCode());
        $this->assertEquals(array('id' => 1), $view->getData());
    }

    public function testResponseGetByNotExistingId()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function ($id) {
            return null;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testProcessPutEmpty()
    {
        $mock = $this->getMock('stdClass', array('delete', 'update', 'add'));
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->never())->method('add');

        $delete = function () use ($mock) {
            $mock->delete();
        };

        $update = function () use ($mock) {
            $mock->update();
        };

        $add = function () use ($mock) {
            $mock->add();
        };

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'processPut');
        $method->setAccessible(true);

        $method->invoke($this->controller, array(), array(), $delete, $update, $add);
    }

    public function testProcessPutWithDelete()
    {
        $mock = $this->getMock('stdClass', array('delete', 'update', 'add'));
        $mock->expects($this->once())->method('delete');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->never())->method('add');

        $delete = function () use ($mock) {
            $mock->delete();
        };

        $update = function () use ($mock) {
            $mock->update();
        };

        $add = function () use ($mock) {
            $mock->add();
        };

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'processPut');
        $method->setAccessible(true);

        $method->invoke(
            $this->controller,
            array(
                $this->mockedObject
            ),
            array(),
            $delete,
            $update,
            $add
        );
    }

    public function testProcessPutWithUpdate()
    {
        $mock = $this->getMock('stdClass', array('delete', 'update', 'add'));
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->once())->method('update');
        $mock->expects($this->never())->method('add');

        $delete = function () use ($mock) {
            $mock->delete();
        };

        $update = function () use ($mock) {
            $mock->update();
        };

        $add = function () use ($mock) {
            $mock->add();
        };

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'processPut');
        $method->setAccessible(true);

        $method->invoke(
            $this->controller,
            array(
                $this->mockedObject
            ),
            array(
                array(
                    'id' => 1
                )
            ),
            $delete,
            $update,
            $add
        );
    }

    public function testProcessPutWithAdd()
    {
        $mock = $this->getMock('stdClass', array('delete', 'update', 'add'));
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->once())->method('add');

        $delete = function () use ($mock) {
            $mock->delete();
        };

        $update = function () use ($mock) {
            $mock->update();
        };

        $add = function () use ($mock) {
            $mock->add();
        };

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'processPut');
        $method->setAccessible(true);

        $method->invoke(
            $this->controller,
            array(),
            array(
                array(
                    'id' => 1
                )
            ),
            $delete,
            $update,
            $add
        );
    }

    public function testDelete()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function ($id) {
            return true;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(204, $view->getStatusCode());
        $this->assertEquals(null, $view->getData());
    }

    public function testDeleteWithNotExistingEntity()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function ($id) {
            throw new EntityNotFoundException('SuluCoreBundle:Example', 7);
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testDeleteWithError()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function ($id) {
            throw new RestException();
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(400, $view->getStatusCode());
    }
}
