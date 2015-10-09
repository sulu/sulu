<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function getProvider()
    {
        return [
            ['c', '', []],
            ['c', '1', ['1']],
            ['c', '1,2', ['1', '2']],
            ['c', '1,2,3', ['1', '2', '3']],
            ['c', '1, 2', ['1', '2']],
            ['c', ' 1, 2 ', ['1', '2']],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($parameter, $queryString, $expected)
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->getCategories($parameter);

        $this->assertEquals($expected, $result);
    }

    public function appendProvider()
    {
        return [
            ['c', '/test', '1,2', '1,2,3'],
            ['categories', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '2,1', '2,1,3'],
            ['categories', '/test', '1,2', '1,2,3'],
            ['categories', '/test', '2,1', '2,1,3'],
            ['categories', '/test', '1,3', '1,3'],
            ['categories', '/test', '', '3'],
        ];
    }

    /**
     * @dataProvider appendProvider
     */
    public function testAppendToUrl($parameter, $url, $queryString, $expected)
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->appendCategoryToUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . urlencode($expected), $result);
    }

    public function setProvider()
    {
        return [
            ['c', '/test', '1,2', '3'],
            ['categories', '/asdf', '1,2', '3'],
            ['categories', '/asdf', '2,1', '3'],
            ['c', '/asdf', '1,2', '3'],
            ['c', '/asdf', '2,1', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '', '3'],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSetToUrl($parameter, $url, $queryString, $expected)
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->setCategoryToUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . urlencode($expected), $result);
    }

    public function removeProvider()
    {
        return [
            ['c', '/test', '1,2'],
            ['c', '/asdf', '1,2'],
            ['categories', '/asdf', '1,2'],
            ['categories', '/test', '1,2'],
            ['categories', '/test', '2,1'],
            ['categories', '/test', '1,3'],
            ['categories', '/test', ''],
        ];
    }

    /**
     * @dataProvider removeProvider
     */
    public function testRemoveFromUrl($parameter, $url, $queryString)
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->removeCategoriesFromUrl($parameter);

        $this->assertEquals($url, $result);
    }
}
