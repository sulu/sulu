<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RedirectController
     */
    private $controller;

    protected function setUp()
    {
        $this->controller = new RedirectController();
    }

    private function getRequestMock($requestUrl, $portalUrl, $redirectUrl = null, $prefix = '')
    {
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                [
                    ['url', null, false, $portalUrl],
                    ['redirect', null, false, $redirectUrl],
                    ['_sulu', null, false, new RequestAttributes(['resourceLocatorPrefix' => $prefix])],
                ]
            )
        );
        $request->expects($this->any())->method('getUri')->will($this->returnValue($requestUrl));

        return $request;
    }

    public function provideRedirectAction()
    {
        return [
            ['http://sulu.lo/articles?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/articles?foo=bar&bar=boo', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['http://sulu.lo/articles/?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/articles/?foo=bar&bar=boo', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['http://sulu.lo/en/articles/?foo=bar', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/en/articles/?foo=bar&bar=boo', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['sulu.lo:8001/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en'],
            ['sulu.lo:8001/#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en#foobar'],
            ['sulu.lo:8001/articles#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en/articles#foobar'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['http://sulu.lo:8002/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8002/en'],
            ['http://sulu.lo/articles', 'sulu.lo/en', 'sulu.lo/de', 'http://sulu.lo/de/articles'],
            ['http://sulu.lo/events', 'sulu.lo/events', 'sulu.lo/events/de', 'http://sulu.lo/events/de', '/events'],
            ['http://sulu.lo/events/articles', 'sulu.lo/events', 'sulu.lo/events/de', 'http://sulu.lo/events/de/articles', '/events'],
        ];
    }

    /**
     * @dataProvider provideRedirectAction
     */
    public function testRedirectAction($requestUri, $portalUrl, $redirectUrl, $expectedTargetUrl, $prefix = '')
    {
        $request = $this->getRequestMock($requestUri, $portalUrl, $redirectUrl, $prefix);

        $response = $this->controller->redirectWebspaceAction($request);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals($expectedTargetUrl, $response->getTargetUrl());
    }

    public function provideRedirectWebspaceAction()
    {
        return [
            ['sulu.lo/de', 'http://sulu.lo', 'http://sulu.lo/de'],
            ['http://sulu.lo/de', 'http://sulu.lo', 'http://sulu.lo/de'],
            ['http://sulu.lo', 'http://sulu.lo/de/test', 'http://sulu.lo/de/test'],
            ['sulu.lo/de', 'http://sulu.lo?test1=value1', 'http://sulu.lo/de?test1=value1'],
        ];
    }

    /**
     * @dataProvider provideRedirectWebspaceAction
     */
    public function testRedirectWebspaceAction($uri, $redirectUri, $expectedTargetUrl)
    {
        $request = $this->getRequestMock($redirectUri, null, $uri);

        $response = $this->controller->redirectWebspaceAction($request);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals($expectedTargetUrl, $response->getTargetUrl());
    }

    public function provideRedirectToRouteAction()
    {
        return [
            ['', 410],
            ['', 404, false],
            ['test'],
            ['test', 302, false],
            ['test', 302, false, ['test' => 1]],
            ['test', 301, true, ['test' => 1]],
            ['test', 302, false, [], ['test' => 1]],
            ['test', 301, true, [], ['test' => 1]],
            ['test', 302, false, ['test-1' => 1], ['test-2' => 1]],
        ];
    }

    /**
     * @dataProvider provideRedirectToRouteAction
     */
    public function testRedirectToRouteAction(
        $route,
        $statusCode = 301,
        $permanent = true,
        $attributesData = [],
        $queryData = []
    ) {
        if ($statusCode >= 400) {
            $this->setExpectedException(HttpException::class);
        }

        $attributes = $this->prophesize(ParameterBag::class);
        $attributes->get('_route_params')->willReturn(
            array_merge($attributesData, ['route' => $route, 'permanent' => $permanent])
        );

        $query = $this->prophesize(ParameterBag::class);
        $query->all()->willReturn($queryData);

        $router = $this->prophesize(RouterInterface::class);
        $router->generate($route, array_merge($attributesData, $queryData), UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('/test-route');

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('router')->willReturn($router->reveal());

        $request = $this->prophesize(Request::class);
        $request->reveal()->attributes = $attributes->reveal();
        $request->reveal()->query = $query->reveal();

        $this->controller->setContainer($container->reveal());

        $response = $this->controller->redirectToRouteAction($request->reveal(), $route, $permanent);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('/test-route', $response->getTargetUrl());
    }
}
