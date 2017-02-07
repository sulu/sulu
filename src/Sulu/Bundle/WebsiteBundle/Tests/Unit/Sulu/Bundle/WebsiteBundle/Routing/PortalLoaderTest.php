<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Routing\PortalLoader;
use Sulu\Bundle\WebsiteBundle\Routing\PortalRoute;
use Sulu\Component\Localization\Localization;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PortalLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalLoader
     */
    private $portalLoader;

    /**
     * @var LoaderResolverInterface
     */
    private $loaderResolver;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var Localization[]
     */
    private $localizations;

    /**
     * @var string
     */
    private $condition = 'request.get("_sulu").getAttribute("portalInformation") !== null && request.get("_sulu").getAttribute("portalInformation").getType() === 1';

    public function setUp()
    {
        parent::setUp();

        $this->loaderResolver = $this->prophesize(LoaderResolverInterface::class);
        $this->loader = $this->prophesize(LoaderInterface::class);

        $this->portalLoader = new PortalLoader();
        $this->portalLoader->setResolver($this->loaderResolver->reveal());

        $de = new Localization();
        $de->setLanguage('de');
        $en = new Localization();
        $en->setLanguage('en');
        $this->localizations = [$de, $en];
    }

    public function testLoad()
    {
        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route1', new Route('/example/route1'));
        $importedRouteCollection->add('route2', new Route('/route2'));

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(2, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route1', $routes);
        $this->assertArrayHasKey('route2', $routes);

        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route1'));
        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route2'));

        $this->assertEquals('{prefix}/example/route1', $routeCollection->get('route1')->getPath());
        $this->assertEquals('{prefix}/route2', $routeCollection->get('route2')->getPath());
        $this->assertEquals('{host}', $routeCollection->get('route1')->getHost());
        $this->assertEquals('{host}', $routeCollection->get('route2')->getHost());
        $this->assertEquals($this->condition, $routeCollection->get('route1')->getCondition());
        $this->assertEquals($this->condition, $routeCollection->get('route2')->getCondition());
    }

    public function testLoadWithCustomUrls()
    {
        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route1', new Route('/example/route1'));
        $importedRouteCollection->add('route2', new Route('/route2'));

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(2, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route1', $routes);
        $this->assertArrayHasKey('route2', $routes);

        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route1'));
        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route2'));

        $this->assertEquals('{prefix}/example/route1', $routeCollection->get('route1')->getPath());
        $this->assertEquals('{prefix}/route2', $routeCollection->get('route2')->getPath());
        $this->assertEquals('{host}', $routeCollection->get('route1')->getHost());
        $this->assertEquals('{host}', $routeCollection->get('route2')->getHost());
        $this->assertEquals($this->condition, $routeCollection->get('route1')->getCondition());
        $this->assertEquals($this->condition, $routeCollection->get('route2')->getCondition());
    }

    public function testLoadSingleRoute()
    {
        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', new Route('/route'));

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route'));

        $this->assertEquals('{prefix}/route', $routeCollection->get('route')->getPath());
        $this->assertEquals('{host}', $routeCollection->get('route')->getHost());
        $this->assertEquals($this->condition, $routeCollection->get('route')->getCondition());
    }

    public function testLoadSingleRouteWithHost()
    {
        $route = new Route('/route');
        $route->setHost('sulu.io');

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', $route);

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route'));

        $this->assertEquals('{prefix}/route', $routeCollection->get('route')->getPath());
        $this->assertEquals('sulu.io', $routeCollection->get('route')->getHost());
        $this->assertEquals($this->condition, $routeCollection->get('route')->getCondition());
    }

    public function testLoadSingleRouteWithCondition()
    {
        $route = new Route('/route');
        $route->setHost('sulu.io');
        $route->setCondition('request.get("test") === "sulu.io"');

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', $route);

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(PortalRoute::class, $routeCollection->get('route'));

        $this->assertEquals('{prefix}/route', $routeCollection->get('route')->getPath());
        $this->assertEquals('sulu.io', $routeCollection->get('route')->getHost());
        $this->assertEquals(
            $this->condition . ' and (request.get("test") === "sulu.io")',
            $routeCollection->get('route')->getCondition()
        );
    }
}
