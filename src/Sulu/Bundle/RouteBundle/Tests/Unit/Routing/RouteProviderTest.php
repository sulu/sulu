<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SuluBundle\Tests\Unit\Routing;

use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Bundle\RouteBundle\Routing\RouteProvider;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;

class RouteProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteProvider
     */
    private $routeProvider;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouteDefaultsProviderInterface
     */
    private $defaultsProvider;

    protected function setUp()
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->defaultsProvider = $this->prophesize(RouteDefaultsProviderInterface::class);

        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->routeProvider = new RouteProvider(
            $this->routeRepository->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->defaultsProvider->reveal()
        );
    }

    public function testGetRouteCollectionForRequestNoRoute()
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn(null);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequestNoSupport()
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->isHistory()->willReturn(false);

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(false);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequest()
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->isHistory()->willReturn(false);

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = array_values(iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestWithHistory()
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getQueryString()->willReturn('test=1');
        $request->getSchemeAndHttpHost()->willReturn('http://www.sulu.io');

        $targetEntity = $this->prophesize(RouteInterface::class);
        $targetEntity->getPath()->willReturn('/test-2');

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getTarget()->willReturn($targetEntity->reveal());
        $routeEntity->isHistory()->willReturn(true);

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = array_values(iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(
            ['_controller' => 'SuluWebsiteBundle:Redirect:redirect', 'url' => 'http://www.sulu.io/de/test-2?test=1'],
            $routes[0]->getDefaults()
        );
    }

    public function testGetRouteCollectionForRequestWithHistoryWithoutQueryString()
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getQueryString()->willReturn(null);
        $request->getSchemeAndHttpHost()->willReturn('http://www.sulu.io');

        $targetEntity = $this->prophesize(RouteInterface::class);
        $targetEntity->getPath()->willReturn('/test-2');

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getTarget()->willReturn($targetEntity->reveal());
        $routeEntity->isHistory()->willReturn(true);

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = array_values(iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(
            ['_controller' => 'SuluWebsiteBundle:Redirect:redirect', 'url' => 'http://www.sulu.io/de/test-2'],
            $routes[0]->getDefaults()
        );
    }

    public function testGetRouteCollectionForRequestNoPrefix()
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/test');
        $request->getLocale()->willReturn('de');

        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn(null);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->isHistory()->willReturn(false);

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = array_values(iterator_to_array($collection->getIterator()));

        $this->assertEquals('/test', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }
}
