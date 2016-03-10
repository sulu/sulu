<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Prophecy\Argument;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
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
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var LoaderResolverInterface
     */
    private $loaderResolver;

    /**
     * @var LoaderInterface
     */
    private $loader;

    public function setUp()
    {
        parent::setUp();

        $this->webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        $this->loaderResolver = $this->prophesize('Symfony\Component\Config\Loader\LoaderResolverInterface');
        $this->loader = $this->prophesize('Symfony\Component\Config\Loader\LoaderInterface');

        $this->portalLoader = new PortalLoader($this->webspaceManager->reveal(), 'dev');
        $this->portalLoader->setResolver($this->loaderResolver->reveal());
    }

    public function testLoad()
    {
        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route1', new Route('/example/route1'));
        $importedRouteCollection->add('route2', new Route('/route2'));

        $portal1 = new Portal();
        $portal1->setKey('sulu_lo');

        $portal2 = new Portal();
        $portal2->setKey('sulu_com');

        $portalInformations = [
            new PortalInformation(null, null, $portal1, null, 'sulu.io/de'),
            new PortalInformation(null, null, $portal2, null, 'sulu.com'),
        ];

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);
        $this->webspaceManager->getPortalInformations(Argument::any())->willReturn($portalInformations);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(4, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('sulu.io/de.route1', $routes);
        $this->assertArrayHasKey('sulu.io/de.route2', $routes);
        $this->assertArrayHasKey('sulu.com.route1', $routes);
        $this->assertArrayHasKey('sulu.com.route2', $routes);

        $this->assertEquals('/de/example/route1', $routeCollection->get('sulu.io/de.route1')->getPath());
        $this->assertEquals('/de/route2', $routeCollection->get('sulu.io/de.route2')->getPath());
        $this->assertEquals('/example/route1', $routeCollection->get('sulu.com.route1')->getPath());
        $this->assertEquals('/route2', $routeCollection->get('sulu.com.route2')->getPath());
    }

    public function testLoadPartial()
    {
        $importedRouteCollection = new RouteCollection();
        $importedRouteCollection->add('route', new Route('/route'));

        $portal = new Portal();
        $portal->setKey('sulu_lo');

        $portalInformations = [
            new PortalInformation(null, null, $portal, null, 'sulu.io/de'),
            new PortalInformation(
                RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                null,
                $portal,
                null,
                'sulu.io',
                null,
                'sulu.io/de'
            ),
        ];

        $this->loaderResolver->resolve(Argument::any(), Argument::any())->willReturn($this->loader->reveal());
        $this->loader->load(Argument::any(), Argument::any())->willReturn($importedRouteCollection);
        $this->webspaceManager->getPortalInformations(Argument::any())->willReturn($portalInformations);

        $routeCollection = $this->portalLoader->load('', 'portal');

        $this->assertCount(2, $routeCollection);

        $routes = $routeCollection->getIterator();
        $this->assertArrayHasKey('sulu.io.route', $routes);
        $this->assertArrayHasKey('sulu.io/de.route', $routes);
        $this->assertEquals('/route', $routeCollection->get('sulu.io.route')->getPath());
        $this->assertEquals('/de/route', $routeCollection->get('sulu.io/de.route')->getPath());
        $this->assertEquals(
            [
                '_controller' => 'SuluWebsiteBundle:Redirect:redirectToRoute',
                'route' => 'sulu.io/de.route',
                'permanent' => true,
            ],
            $routeCollection->get('sulu.io.route')->getDefaults()
        );
    }
}
