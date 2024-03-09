<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Routing\PortalLoader;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PortalLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var PortalLoader
     */
    private $portalLoader;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<LoaderResolverInterface>
     */
    private $loaderResolver;

    /**
     * @var ObjectProphecy<LoaderInterface>
     */
    private $loader;

    /**
     * @var Localization[]
     */
    private $localizations;

    public function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->loaderResolver = $this->prophesize(LoaderResolverInterface::class);
        $this->loader = $this->prophesize(LoaderInterface::class);

        $this->portalLoader = new PortalLoader(
            $this->webspaceManager->reveal(),
            new FileLocator()
        );
        $this->portalLoader->setResolver($this->loaderResolver->reveal());

        $this->localizations = [new Localization('de'), new Localization('en')];
    }

    public function testLoad(): void
    {
        $portalInformation1 = $this->prophesize(PortalInformation::class);
        $portalInformation1->getPrefix()->willReturn('de/');

        $portalInformation2 = $this->prophesize(PortalInformation::class);
        $portalInformation2->getPrefix()->willReturn('en/');

        $portalInformation3 = $this->prophesize(PortalInformation::class);
        $portalInformation3->getPrefix()->willReturn('');

        $portalInformation4 = $this->prophesize(PortalInformation::class);
        $portalInformation4->getPrefix()->willReturn('custom/pre-fix/');

        $this->webspaceManager->getPortalInformations()->willReturn([
            $portalInformation1->reveal(),
            $portalInformation2->reveal(),
            $portalInformation3->reveal(),
            $portalInformation4->reveal(),
        ]);

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route1', new Route('/example/route1'));
        $importedRouteCollection->add('route2', new Route('/route2'));

        $this->loaderResolver->resolve('routes.yml', null)->willReturn($this->loader->reveal());
        $this->loader->load('routes.yml', null)->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('routes.yml', 'portal');

        $this->assertCount(2, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route1', $routes);
        $this->assertArrayHasKey('route2', $routes);

        $this->assertInstanceOf(Route::class, $routeCollection->get('route1'));
        $this->assertInstanceOf(Route::class, $routeCollection->get('route2'));

        $this->assertEquals('/{prefix}example/route1', $routeCollection->get('route1')->getPath());
        $this->assertEquals(['prefix' => 'de/|en/|(^$)?|custom/pre\-fix/'], $routeCollection->get('route1')->getRequirements());
        $this->assertEquals('/{prefix}route2', $routeCollection->get('route2')->getPath());
        $this->assertEquals(['prefix' => 'de/|en/|(^$)?|custom/pre\-fix/'], $routeCollection->get('route2')->getRequirements());
        $this->assertEquals('', $routeCollection->get('route1')->getHost());
        $this->assertEquals('', $routeCollection->get('route2')->getHost());
    }

    public function testLoadWithEmptyPortalPrefix(): void
    {
        $portalInformation1 = $this->prophesize(PortalInformation::class);
        $portalInformation1->getPrefix()->willReturn('');

        $this->webspaceManager->getPortalInformations()->willReturn([
            $portalInformation1->reveal(),
        ]);

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route1', new Route('/example/route1'));
        $importedRouteCollection->add('route2', new Route('/route2'));

        $this->loaderResolver->resolve('routes.yml', null)->willReturn($this->loader->reveal());
        $this->loader->load('routes.yml', null)->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('routes.yml', 'portal');

        $this->assertCount(2, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route1', $routes);
        $this->assertArrayHasKey('route2', $routes);

        $this->assertInstanceOf(Route::class, $routeCollection->get('route1'));
        $this->assertInstanceOf(Route::class, $routeCollection->get('route2'));

        $this->assertEquals('/{prefix}example/route1', $routeCollection->get('route1')->getPath());
        $this->assertEquals(['prefix' => '(^$)?'], $routeCollection->get('route1')->getRequirements());
        $this->assertEquals('/{prefix}route2', $routeCollection->get('route2')->getPath());
        $this->assertEquals(['prefix' => '(^$)?'], $routeCollection->get('route2')->getRequirements());
        $this->assertEquals('', $routeCollection->get('route1')->getHost());
        $this->assertEquals('', $routeCollection->get('route2')->getHost());
    }

    public function testLoadSingleRoute(): void
    {
        $portalInformation1 = $this->prophesize(PortalInformation::class);
        $portalInformation1->getPrefix()->willReturn('de/');

        $portalInformation2 = $this->prophesize(PortalInformation::class);
        $portalInformation2->getPrefix()->willReturn('en/');

        $portalInformation3 = $this->prophesize(PortalInformation::class);
        $portalInformation3->getPrefix()->willReturn('custom/prefix/');

        $this->webspaceManager->getPortalInformations()->willReturn([
            $portalInformation1->reveal(),
            $portalInformation2->reveal(),
            $portalInformation3->reveal(),
        ]);

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', new Route('/route'));

        $this->loaderResolver->resolve('routes.yml', null)->willReturn($this->loader->reveal());
        $this->loader->load('routes.yml', null)->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('routes.yml', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(Route::class, $routeCollection->get('route'));

        $this->assertEquals('/{prefix}route', $routeCollection->get('route')->getPath());
        $this->assertEquals(['prefix' => 'de/|en/|custom/prefix/'], $routeCollection->get('route')->getRequirements());
        $this->assertEquals('', $routeCollection->get('route')->getHost());
    }

    public function testLoadSingleRouteWithHost(): void
    {
        $portalInformation1 = $this->prophesize(PortalInformation::class);
        $portalInformation1->getPrefix()->willReturn('de/');

        $portalInformation2 = $this->prophesize(PortalInformation::class);
        $portalInformation2->getPrefix()->willReturn('en/');

        $portalInformation3 = $this->prophesize(PortalInformation::class);
        $portalInformation3->getPrefix()->willReturn('custom/prefix/');

        $this->webspaceManager->getPortalInformations()->willReturn([
            $portalInformation1->reveal(),
            $portalInformation2->reveal(),
            $portalInformation3->reveal(),
        ]);

        $route = new Route('/route');
        $route->setHost('sulu.io');

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', $route);

        $this->loaderResolver->resolve('routes.yml', null)->willReturn($this->loader->reveal());
        $this->loader->load('routes.yml', null)->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('routes.yml', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(Route::class, $routeCollection->get('route'));

        $this->assertEquals('/{prefix}route', $routeCollection->get('route')->getPath());
        $this->assertEquals(['prefix' => 'de/|en/|custom/prefix/'], $routeCollection->get('route')->getRequirements());
        $this->assertEquals('sulu.io', $routeCollection->get('route')->getHost());
    }

    public function testLoadSingleRouteWithCondition(): void
    {
        $portalInformation1 = $this->prophesize(PortalInformation::class);
        $portalInformation1->getPrefix()->willReturn('de/');

        $portalInformation2 = $this->prophesize(PortalInformation::class);
        $portalInformation2->getPrefix()->willReturn('en/');

        $portalInformation3 = $this->prophesize(PortalInformation::class);
        $portalInformation3->getPrefix()->willReturn('custom/prefix/');

        $this->webspaceManager->getPortalInformations()->willReturn([
            $portalInformation1->reveal(),
            $portalInformation2->reveal(),
            $portalInformation3->reveal(),
        ]);

        $route = new Route('/route');
        $route->setHost('sulu.io');
        $route->setCondition('request.get("test") === "sulu.io"');

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', $route);

        $this->loaderResolver->resolve('routes.yml', null)->willReturn($this->loader->reveal());
        $this->loader->load('routes.yml', null)->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('routes.yml', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(Route::class, $routeCollection->get('route'));

        $this->assertEquals('/{prefix}route', $routeCollection->get('route')->getPath());
        $this->assertEquals(['prefix' => 'de/|en/|custom/prefix/'], $routeCollection->get('route')->getRequirements());
        $this->assertEquals('sulu.io', $routeCollection->get('route')->getHost());
        $this->assertEquals('request.get("test") === "sulu.io"', $routeCollection->get('route')->getCondition());
    }

    public function testLoadWithRequirements(): void
    {
        $portalInformation1 = $this->prophesize(PortalInformation::class);
        $portalInformation1->getPrefix()->willReturn('de/');

        $portalInformation2 = $this->prophesize(PortalInformation::class);
        $portalInformation2->getPrefix()->willReturn('en/');

        $portalInformation3 = $this->prophesize(PortalInformation::class);
        $portalInformation3->getPrefix()->willReturn('custom/prefix/');

        $this->webspaceManager->getPortalInformations()->willReturn([
            $portalInformation1->reveal(),
            $portalInformation2->reveal(),
            $portalInformation3->reveal(),
        ]);

        $route = new Route('/route', [], ['requirement1' => '\d+']);
        $route->setHost('sulu.io');

        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', $route);

        $this->loaderResolver->resolve('routes.yml', null)->willReturn($this->loader->reveal());
        $this->loader->load('routes.yml', null)->willReturn($importedRouteCollection);

        $routeCollection = $this->portalLoader->load('routes.yml', 'portal');

        $this->assertCount(1, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('route', $routes);

        $this->assertInstanceOf(Route::class, $routeCollection->get('route'));

        $this->assertEquals(
            ['prefix' => 'de/|en/|custom/prefix/', 'requirement1' => '\d+'],
            $routeCollection->get('route')->getRequirements()
        );
    }
}
