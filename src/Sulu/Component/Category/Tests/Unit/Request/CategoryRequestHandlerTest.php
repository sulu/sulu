<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Category\Request\CategoryRequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryRequestHandlerTest extends TestCase
{
    use ProphecyTrait;

    public static function getProvider()
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
     * @param string $parameter
     * @param string $queryString
     * @param string[] $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getProvider')]
    public function testGet($parameter, $queryString, $expected): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/');
        $requestStack->push($request);
        $request->query->set($parameter, $queryString);

        $handler = new CategoryRequestHandler($requestStack);
        $result = $handler->getCategories($parameter);

        $this->assertEquals($expected, $result);
    }

    public static function appendProvider()
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
     * @param string $parameter
     * @param string $url
     * @param string $queryString
     * @param string $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('appendProvider')]
    public function testAppendToUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = new RequestStack();
        $request = Request::create($url);
        $requestStack->push($request);
        $request->query->set($parameter, $queryString);

        $handler = new CategoryRequestHandler($requestStack);
        $result = $handler->appendCategoryToUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function removeSingleProvider()
    {
        return [
            ['c', '/test', '1,2,3', '1,2'],
            ['c', '/asdf', '1,2', '1,2'],
            ['c', '/asdf', '3', ''],
            ['categories', '/asdf', '1,2', '1,2'],
            ['categories', '/test', '1,3,2', '1,2'],
            ['categories', '/test', '3,1', '1'],
            ['categories', '/test', '1,3', '1'],
            ['categories', '/test', '', ''],
        ];
    }

    /**
     * @param string $parameter
     * @param string $url
     * @param string $queryString
     * @param string $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('removeSingleProvider')]
    public function testRemoveSingleFromUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = new RequestStack();
        $request = Request::create($url);
        $requestStack->push($request);
        $request->query->set($parameter, $queryString);

        $handler = new CategoryRequestHandler($requestStack);
        $result = $handler->removeCategoryFromUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function toggleProvider()
    {
        return [
            ['c', '/test', '1,2', '1,2,3'],
            ['c', '/asdf', '1,3', '1'],
            ['c', '/asdf', '2,1', '2,1,3'],
            ['categories', '/asdf', '1,2', '1,2,3'],
            ['categories', '/test', '3,2', '2'],
            ['categories', '/test', '1,3', '1'],
            ['categories', '/test', '3', ''],
            ['categories', '/test', '', '3'],
        ];
    }

    /**
     * @param string $parameter
     * @param string $url
     * @param string $queryString
     * @param string $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('toggleProvider')]
    public function testToggleToUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = new RequestStack();
        $request = Request::create($url);
        $requestStack->push($request);
        $request->query->set($parameter, $queryString);

        $handler = new CategoryRequestHandler($requestStack);
        $result = $handler->toggleCategoryInUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function setProvider()
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
     * @param string $parameter
     * @param string $url
     * @param string $queryString
     * @param string $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('setProvider')]
    public function testSetToUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = new RequestStack();
        $request = Request::create($url);
        $requestStack->push($request);
        $request->query->set($parameter, $queryString);

        $handler = new CategoryRequestHandler($requestStack);
        $result = $handler->setCategoryToUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function removeProvider()
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
     * @param string $parameter
     * @param string $url
     * @param string $queryString
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('removeProvider')]
    public function testRemoveFromUrl($parameter, $url, $queryString): void
    {
        $requestStack = new RequestStack();
        $request = Request::create($url);
        $requestStack->push($request);
        $request->query->set($parameter, $queryString);

        $handler = new CategoryRequestHandler($requestStack);
        $result = $handler->removeCategoriesFromUrl($parameter);

        $this->assertEquals($url, $result);
    }
}
