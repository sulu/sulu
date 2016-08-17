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
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\Listing\ListRestHelper;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

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
        $method = new \ReflectionMethod(RestController::class, 'responseGetById');
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
        $delete = function () {
            $this->fail('delete should not be called');
        };

        $update = function () {
            $this->fail('update should not be called');
        };

        $add = function () {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(RestController::class, 'processPut');
        $method->setAccessible(true);

        $method->invoke($this->controller, [], [], $delete, $update, $add);
    }

    public function testProcessPutWithDelete()
    {
        $deleteCalled = false;
        $delete = function () use (&$deleteCalled) {
            $deleteCalled = true;
        };

        $update = function () {
            $this->fail('update should not be called');
        };

        $add = function () {
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
        $delete = function () {
            $this->fail('delete should not be called');
        };

        $updateCalled = false;
        $update = function () use (&$updateCalled) {
            $updateCalled = true;
        };

        $add = function () {
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
        $delete = function () {
            $this->fail('delete should not be called');
        };

        $update = function () {
            $this->fail('update shoudl not be called');
        };

        $addCalled = false;
        $add = function () use (&$addCalled) {
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
        $method = new \ReflectionMethod(RestController::class, 'responseDelete');
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
        $method = new \ReflectionMethod(RestController::class, 'responseDelete');
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
            RestController::class,
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );

        $listHelper = $this->prophesize(ListRestHelper::class);
        $listHelper->find(null, $entities, [])->willReturn($entities);
        $listHelper->getTotalNumberOfElements(null, $entities, [])->willReturn(3);
        $listHelper->getTotalPages(3)->willReturn(3);
        $listHelper->getLimit()->willReturn(1);
        $listHelper->getPage()->willReturn(2);

        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper->reveal()));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'admin/api/contacts?page=2']);
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $method = new \ReflectionMethod(RestController::class, 'responseList');
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
            RestController::class,
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );

        $listHelper = $this->prophesize(ListRestHelper::class);
        $listHelper->find(null, $entities, [])->willReturn($entities);
        $listHelper->getTotalNumberOfElements(null, $entities, [])->willReturn(3);
        $listHelper->getTotalPages(3)->willReturn(3);
        $listHelper->getLimit()->willReturn(4);
        $listHelper->getPage()->willReturn(2);

        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper->reveal()));

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['REQUEST_URI' => 'admin/api/contacts?flat=true&page=2&limit=4&orderBy=lastName&sortOrder=asc']
        );
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $method = new \ReflectionMethod(RestController::class, 'responseList');
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
            RestController::class,
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );

        $listHelper = $this->prophesize(ListRestHelper::class);
        $listHelper->find(null, $entities, [])->willReturn($entities);
        $listHelper->getTotalNumberOfElements(null, $entities, [])->willReturn(3);
        $listHelper->getTotalPages(3)->willReturn(3);
        $listHelper->getLimit()->willReturn(1);
        $listHelper->getPage()->willReturn(2);

        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper->reveal()));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'admin/api/contacts?page=2']);
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $method = new \ReflectionMethod(RestController::class, 'createHalResponse');
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
            $this->prophesize(ApiEntity::class),
            $this->prophesize(ApiEntity::class),
        ];

        $listHelper = $this->prophesize(ListRestHelper::class);

        $listHelper->getLimit()->willReturn(1);
        $listHelper->getPage()->willReturn(2);

        $controller = $this->getMockForAbstractClass(
            RestController::class,
            [],
            '',
            true,
            true,
            true,
            ['get', 'getRequest']
        );
        $controller->expects($this->any())->method('get')->will($this->returnValue($listHelper->reveal()));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/admin/api/contacts']);
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $method = new \ReflectionMethod(RestController::class, 'getHalLinks');
        $method->setAccessible(true);

        /** @var View $view */
        $view = $method->invoke($controller, $entities);

        $this->assertEquals($view['self'], '/admin/api/contacts');
    }
}
