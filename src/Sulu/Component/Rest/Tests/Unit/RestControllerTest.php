<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use FOS\RestBundle\View\View;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;

class RestControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sulu\Component\Rest\RestController
     */
    protected $controller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockedObject;

    public function setUp()
    {
        $this->controller = $this->getMockForAbstractClass('\Sulu\Component\Rest\RestController');
        $this->mockedObject = $this->getMock('stdClass', ['getId']);
        $this->mockedObject->expects($this->any())->method('getId')->will($this->returnValue(1));
    }

    public function testResponseGetById()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function ($id) {
            return ['id' => $id];
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(200, $view->getStatusCode());
        $this->assertEquals(['id' => 1], $view->getData());
    }

    public function testResponseGetByNotExistingId()
    {
        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function ($id) {
            return;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testProcessPutEmpty()
    {
        $mock = $this->getMock('stdClass', ['delete', 'update', 'add']);
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

        $method->invoke($this->controller, [], [], $delete, $update, $add);
    }

    public function testProcessPutWithDelete()
    {
        $mock = $this->getMock('stdClass', ['delete', 'update', 'add']);
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
            [
                $this->mockedObject,
            ],
            [],
            $delete,
            $update,
            $add
        );
    }

    public function testProcessPutWithUpdate()
    {
        $mock = $this->getMock('stdClass', ['delete', 'update', 'add']);
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
            [
                $this->mockedObject,
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
    }

    public function testProcessPutWithAdd()
    {
        $mock = $this->getMock('stdClass', ['delete', 'update', 'add']);
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

    public function testResponseList()
    {
        $entities = [
            [
                'test' => 1,
            ],
            [
                'test' => 2,
            ],
            [
                'test' => 3,
            ],
        ];

        $controller = $this->getMockForAbstractClass(
            '\Sulu\Component\Rest\RestController',
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );

        $listHelper = $this->getMock(
            '\Sulu\Bundle\Rest\Listing\ListRestHelper',
            ['find', 'getTotalPages', 'getTotalNumberOfElements', 'getLimit', 'getPage']
        );

        $listHelper->expects($this->any())->method('find')->will($this->returnValue($entities));
        $listHelper->expects($this->any())->method('getTotalPages')->will($this->returnValue(3));

        $listHelper->expects($this->any())->method('getLimit')->will($this->returnValue(1));
        $listHelper->expects($this->any())->method('getPage')->will($this->returnValue(2));

        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper));

        $request = $this->getMock('\Request', ['getRequestUri', 'getPathInfo']);
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('admin/api/contacts?page=2'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('admin/api/contacts'));
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag', ['all']);
        $query->expects($this->any())->method('all')->will($this->returnValue([]));
        $request->query = $query;

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseList');
        $method->setAccessible(true);

        $view = $method->invoke($controller, $entities)->getData();

        $this->assertEquals('admin/api/contacts?page=2', $view['_links']['self']);
        $this->assertEquals('admin/api/contacts?page=1', $view['_links']['first']);
        $this->assertEquals('admin/api/contacts?page=3', $view['_links']['last']);
        $this->assertEquals('admin/api/contacts?page=1', $view['_links']['prev']);
        $this->assertEquals('admin/api/contacts?page=3', $view['_links']['next']);
        $this->assertEquals('admin/api/contacts', $view['_links']['all']);
        $this->assertEquals('admin/api/contacts?page={page}&limit={limit}', $view['_links']['pagination']);
        $this->assertEquals(
            'admin/api/contacts?sortBy=test&sortOrder={sortOrder}',
            $view['_links']['sortable']['test']
        );
        $this->assertEquals('3', $view['total']);
        $this->assertEquals('2', $view['page']);
        $this->assertEquals('3', $view['pages']);
        $this->assertEquals('1', $view['limit']);
        $this->assertEquals(1, $view['_embedded'][0]['test']);
        $this->assertEquals(2, $view['_embedded'][1]['test']);
        $this->assertEquals(2, $view['_embedded'][1]['test']);
        $this->assertEquals(3, $view['_embedded'][2]['test']);
    }

    public function testResponseListForAllValue()
    {
        $entities = [
            [
                'test' => 1,
            ],
            [
                'test' => 2,
            ],
            [
                'test' => 3,
            ],
            [
                'test' => 4,
            ],
            [
                'test' => 5,
            ],
            [
                'test' => 6,
            ],
        ];

        $controller = $this->getMockForAbstractClass(
            '\Sulu\Component\Rest\RestController',
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );

        $listHelper = $this->getMock(
            '\Sulu\Bundle\Rest\Listing\ListRestHelper',
            ['find', 'getTotalPages', 'getTotalNumberOfElements', 'getLimit', 'getPage']
        );

        $listHelper->expects($this->any())->method('find')->will($this->returnValue($entities));
        $listHelper->expects($this->any())->method('getTotalPages')->will($this->returnValue(3));

        $listHelper->expects($this->any())->method('getLimit')->will($this->returnValue(4));
        $listHelper->expects($this->any())->method('getPage')->will($this->returnValue(2));

        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper));

        $request = $this->getMock('\Request', ['getRequestUri', 'getPathInfo']);
        $request->expects($this->any())->method('getRequestUri')->will(
            $this->returnValue('admin/api/contacts?flat=true&page=2&limit=4&orderBy=lastName&sortOrder=asc')
        );
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('admin/api/contacts'));
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag', ['all']);
        $query->expects($this->any())->method('all')->will($this->returnValue([]));
        $request->query = $query;

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'responseList');
        $method->setAccessible(true);

        $view = $method->invoke($controller, $entities)->getData();

        $this->assertEquals(
            'admin/api/contacts?flat=true&page=2&limit=4&orderBy=lastName&sortOrder=asc',
            $view['_links']['self']
        );
        $this->assertEquals(
            'admin/api/contacts?flat=true&page=1&limit=4&orderBy=lastName&sortOrder=asc',
            $view['_links']['prev']
        );
        $this->assertEquals(
            'admin/api/contacts?flat=true&page=3&limit=4&orderBy=lastName&sortOrder=asc',
            $view['_links']['next']
        );
        $this->assertEquals('admin/api/contacts?flat=true&orderBy=lastName&sortOrder=asc', $view['_links']['all']);
    }

    public function testCreateHalResponse()
    {
        $entities = [
            [
                'test' => 1,
            ],
            [
                'test' => 2,
            ],
            [
                'test' => 3,
            ],
        ];

        $controller = $this->getMockForAbstractClass(
            '\Sulu\Component\Rest\RestController',
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );

        $listHelper = $this->getMock(
            '\Sulu\Bundle\Rest\Listing\ListRestHelper',
            ['find', 'getTotalPages', 'getTotalNumberOfElements', 'getLimit', 'getPage']
        );

        $listHelper->expects($this->any())->method('find')->will($this->returnValue($entities));
        $listHelper->expects($this->any())->method('getTotalPages')->will($this->returnValue(3));

        $listHelper->expects($this->any())->method('getLimit')->will($this->returnValue(1));
        $listHelper->expects($this->any())->method('getPage')->will($this->returnValue(2));

        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper));

        $request = $this->getMock('\Request', ['getRequestUri', 'getPathInfo']);
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('admin/api/contacts?page=2'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('admin/api/contacts'));
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag', ['all']);
        $query->expects($this->any())->method('all')->will($this->returnValue([]));
        $request->query = $query;

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'createHalResponse');
        $method->setAccessible(true);

        $view = $method->invoke($controller, $entities);

        $this->assertEquals('admin/api/contacts?page=2', $view['_links']['self']);
        $this->assertEquals('3', $view['total']);
        $this->assertEquals(1, $view['_embedded'][0]['test']);
        $this->assertEquals(2, $view['_embedded'][1]['test']);
        $this->assertEquals(3, $view['_embedded'][2]['test']);
    }

    public function testHalLink()
    {
        $entities = [
            $this->getMockForAbstractClass('\Sulu\Bundle\CoreBundle\Entity\ApiEntity'),
            $this->getMockForAbstractClass('\Sulu\Bundle\CoreBundle\Entity\ApiEntity'),
        ];

        $listHelper = $this->getMock(
            '\Sulu\Bundle\Rest\Listing\ListRestHelper',
            ['getLimit', 'getPage']
        );

        $listHelper->expects($this->any())->method('getLimit')->will($this->returnValue(1));
        $listHelper->expects($this->any())->method('getPage')->will($this->returnValue(2));

        $controller = $this->getMockForAbstractClass(
            '\Sulu\Component\Rest\RestController',
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );
        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper));
        $request = $this->getMock('\Request', ['getRequestUri', 'getPathInfo']);
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue('/admin/api/contacts'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('admin/api/contacts'));
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag', ['all']);
        $query->expects($this->any())->method('all')->will($this->returnValue([]));
        $request->query = $query;

        $method = new \ReflectionMethod('\Sulu\Component\Rest\RestController', 'getHalLinks');
        $method->setAccessible(true);

        /** @var View $view */
        $view = $method->invoke($controller, $entities);

        $this->assertEquals($view['self'], '/admin/api/contacts');
    }
}
