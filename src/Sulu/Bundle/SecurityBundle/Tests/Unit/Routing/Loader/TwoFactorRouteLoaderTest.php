<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Routing\Loader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\SecurityBundle\Routing\Loader\TwoFactorRouteLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;

class TwoFactorRouteLoaderTest extends TestCase
{
    use ProphecyTrait;

    public function testLoadWithoutSetupMethods(): void
    {
        $routeLoader = new TwoFactorRouteLoader([]);

        $routes = $routeLoader->load('routing.yaml');

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertCount(0, $routes);
    }

    public function testLoadWithSetupMethods(): void
    {
        $routeLoader = new TwoFactorRouteLoader(['email']);
        $resolver = $this->prophesize(LoaderResolverInterface::class);

        $routes = new RouteCollection();

        $loader = $this->prophesize(LoaderInterface::class);
        $loader->load('routing.yaml', null)->shouldBeCalled()->willReturn($routes);
        $resolver->resolve('routing.yaml', null)->willReturn($loader->reveal());
        $routeLoader->setResolver($resolver->reveal());

        $this->assertEquals($routes, $routeLoader->load('routing.yaml'));
    }

    public function testSupports(): void
    {
        $routeLoader = new TwoFactorRouteLoader([]);

        $this->assertTrue($routeLoader->supports('routing.yaml', 'sulu_security_two_factor'));
        $this->assertFalse($routeLoader->supports('routing.yaml', 'rest'));
        $this->assertFalse($routeLoader->supports('routing.yaml'));
    }
}
