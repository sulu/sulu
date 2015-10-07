<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Tag\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TagRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function getProvider()
    {
        return [
            ['t', '', []],
            ['t', 'Sulu', ['Sulu']],
            ['t', 'Sulu,Core', ['Sulu', 'Core']],
            ['t', 'Sulu,Core,Massive Art', ['Sulu', 'Core', 'Massive Art']],
            ['t', 'Sulu, Core', ['Sulu', 'Core']],
            ['t', ' Sulu, Core ', ['Sulu', 'Core']],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGetTagsProvider($tagsParameter, $tagsString, $expected)
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);

        $handler = new TagRequestHandler($requestStack->reveal());
        $tags = $handler->getTags($tagsParameter);

        $this->assertEquals($expected, $tags);
    }

    public function appendProvider()
    {
        return [
            ['t', '/test', 'Sulu,Core', 'Sulu,Core,Test'],
            ['tags', '/asdf', 'Sulu,Core', 'Sulu,Core,Test'],
            ['t', '/asdf', 'Sulu,Core', 'Sulu,Core,Test'],
            ['tags', '/test', 'Sulu,Core', 'Sulu,Core,Test'],
            ['tags', '/test', 'Sulu,Test', 'Sulu,Test'],
            ['tags', '/test', '', 'Test'],
        ];
    }

    /**
     * @dataProvider appendProvider
     */
    public function testAppendTagToUrl($tagsParameter, $url, $tagsString, $expected)
    {
        $tag = ['name' => 'Test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);
        $request->getPathInfo()->willReturn($url);

        $handler = new TagRequestHandler($requestStack->reveal());
        $result = $handler->appendTagToUrl($tag, $tagsParameter);

        $this->assertEquals($url . '?' . $tagsParameter . '=' . urlencode($expected), $result);
    }

    public function setProvider()
    {
        return [
            ['t', '/test', 'Sulu,Core', 'Test'],
            ['tags', '/asdf', 'Sulu,Core', 'Test'],
            ['t', '/asdf', 'Sulu,Core', 'Test'],
            ['tags', '/test', 'Sulu,Core', 'Test'],
            ['tags', '/test', 'Sulu,Test', 'Test'],
            ['tags', '/test', '', 'Test'],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSetTagToUrl($tagsParameter, $url, $tagsString, $expected)
    {
        $tag = ['name' => 'Test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);
        $request->getPathInfo()->willReturn($url);

        $handler = new TagRequestHandler($requestStack->reveal());
        $result = $handler->setTagToUrl($tag, $tagsParameter);

        $this->assertEquals($url . '?' . $tagsParameter . '=' . urlencode($expected), $result);
    }

    public function removeProvider()
    {
        return [
            ['t', '/test', 'Sulu,Core'],
            ['t', '/asdf', 'Sulu,Core'],
            ['tags', '/asdf', 'Sulu,Core'],
            ['tags', '/test', 'Sulu,Core'],
            ['tags', '/test', 'Sulu,Test'],
            ['tags', '/test', ''],
        ];
    }

    /**
     * @dataProvider removeProvider
     */
    public function testRemoveTagsFromUrl($tagsParameter, $url, $tagsString)
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);
        $request->getPathInfo()->willReturn($url);

        $handler = new TagRequestHandler($requestStack->reveal());
        $result = $handler->removeTagsFromUrl($tagsParameter);

        $this->assertEquals($url, $result);
    }
}
