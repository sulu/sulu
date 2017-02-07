<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Manager;

use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\AutoIncrementConflictResolver;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class AutoIncrementConflictResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var AutoIncrementConflictResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->resolver = new AutoIncrementConflictResolver($this->routeRepository->reveal());
    }

    public function testResolve()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn(null);

        $result = $this->resolver->resolve($route->reveal());

        $this->assertEquals($route->reveal(), $result);
    }

    public function testResolveWithConflict()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setPath('/test-1')->shouldBeCalled()->will(
            function () use ($route) {
                $route->getPath()->willReturn('/test-1');
            }
        );
        $route->getLocale()->willReturn('de');
        $route->getEntityClass()->willReturn('Test');
        $route->getEntityId()->willReturn('1');

        $conflict = $this->prophesize(RouteInterface::class);
        $conflict->getPath()->willReturn('/test');
        $conflict->getLocale()->willReturn('de');
        $conflict->getEntityClass()->willReturn('Test');
        $conflict->getEntityId()->willReturn('2');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($conflict->reveal());
        $this->routeRepository->findByPath('/test-1', 'de')->willReturn(null);

        $result = $this->resolver->resolve($route->reveal());

        $this->assertEquals($route->reveal(), $result);
    }

    public function testResolveWithConflictTwice()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setPath('/test-1')->shouldBeCalled()->will(
            function () use ($route) {
                $route->getPath()->willReturn('/test-1');
            }
        );
        $route->setPath('/test-2')->shouldBeCalled()->will(
            function () use ($route) {
                $route->getPath()->willReturn('/test-2');
            }
        );
        $route->getLocale()->willReturn('de');
        $route->getEntityClass()->willReturn('Test');
        $route->getEntityId()->willReturn('1');

        $conflict1 = $this->prophesize(RouteInterface::class);
        $conflict1->getPath()->willReturn('/test');
        $conflict1->getLocale()->willReturn('de');
        $conflict1->getEntityClass()->willReturn('Test');
        $conflict1->getEntityId()->willReturn('2');

        $conflict2 = $this->prophesize(RouteInterface::class);
        $conflict2->getPath()->willReturn('/test-1');
        $conflict2->getLocale()->willReturn('de');
        $conflict2->getEntityClass()->willReturn('Test');
        $conflict2->getEntityId()->willReturn('3');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($conflict1->reveal());
        $this->routeRepository->findByPath('/test-1', 'de')->willReturn($conflict2->reveal());
        $this->routeRepository->findByPath('/test-2', 'de')->willReturn(null);

        $result = $this->resolver->resolve($route->reveal());

        $this->assertEquals($route->reveal(), $result);
    }

    public function testResolveWithSame()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');
        $route->getEntityClass()->willReturn('Test');
        $route->getEntityId()->willReturn('1');

        $conflict = $this->prophesize(RouteInterface::class);
        $conflict->getPath()->willReturn('/test');
        $conflict->getLocale()->willReturn('de');
        $conflict->getEntityClass()->willReturn('Test');
        $conflict->getEntityId()->willReturn('1');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($conflict->reveal());

        $result = $this->resolver->resolve($route->reveal());

        $this->assertEquals($conflict->reveal(), $result);
    }
}
