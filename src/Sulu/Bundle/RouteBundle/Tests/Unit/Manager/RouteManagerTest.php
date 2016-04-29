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
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteAlreadyCreatedException;
use Sulu\Bundle\RouteBundle\Manager\RouteManager;
use Sulu\Bundle\RouteBundle\Manager\RouteNotCreatedException;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class RouteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $mappings;

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @var RouteManager
     */
    private $manager;

    /**
     * @var RoutableInterface
     */
    private $entity;

    protected function setUp()
    {
        $this->entity = $this->prophesize(RoutableInterface::class);
        $this->mappings = [get_class($this->entity->reveal()) => ['route_schema' => '/{title}']];

        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);

        $this->manager = new RouteManager(
            $this->routeGenerator->reveal(),
            $this->routeRepository->reveal(),
            $this->conflictResolver->reveal(),
            $this->mappings
        );
    }

    public function testCreate()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->setPath('/test')->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityClass(get_class($this->entity->reveal()))->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityId('1')->shouldBeCalled()->willReturn($route->reveal());
        $route->setLocale('de')->shouldBeCalled()->willReturn($route->reveal());

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test');
        $this->routeRepository->createNew()->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal()));
    }

    public function testCreateAlreadyExists()
    {
        $this->setExpectedException(RouteAlreadyCreatedException::class);

        $route = $this->prophesize(RouteInterface::class);
        $this->entity->getRoute()->willReturn($route->reveal());
        $this->entity->getId()->willReturn('1');

        $this->manager->create($this->entity->reveal());
    }

    public function testCreateWithConflict()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->setPath('/test')->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityClass(get_class($this->entity->reveal()))->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityId('1')->shouldBeCalled()->willReturn($route->reveal());
        $route->setLocale('de')->shouldBeCalled()->willReturn($route->reveal());

        $conflict = $this->prophesize(RouteInterface::class);

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($conflict->reveal())->shouldBeCalled();

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test');
        $this->routeRepository->createNew()->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($conflict->reveal());

        $this->assertEquals($conflict->reveal(), $this->manager->create($this->entity->reveal()));
    }

    public function testUpdate()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->setEntityClass(get_class($this->entity->reveal()))->shouldBeCalled()
            ->willReturn($newRoute->reveal());
        $newRoute->setPath('/test-2')->shouldBeCalled()->will(
            function () use ($newRoute) {
                $newRoute->getPath()->willReturn('/test-2');

                return $newRoute->reveal();
            }
        );
        $newRoute->setEntityId('1')->shouldBeCalled()->willReturn($newRoute->reveal());
        $newRoute->setLocale('de')->shouldBeCalled()->willReturn($newRoute->reveal());
        $newRoute->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($newRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([]);

        $this->entity->setRoute($newRoute->reveal())->shouldBeCalled();

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test-2');
        $this->routeRepository->createNew()->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $this->assertEquals($newRoute->reveal(), $this->manager->update($this->entity->reveal()));
    }

    public function testUpdateWithConflict()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->setEntityClass(get_class($this->entity->reveal()))->shouldBeCalled()
            ->willReturn($newRoute->reveal());
        $newRoute->setPath('/test-2')->shouldBeCalled()->will(
            function () use ($newRoute) {
                $newRoute->getPath()->willReturn('/test-2');

                return $newRoute->reveal();
            }
        );
        $newRoute->setEntityId('1')->shouldBeCalled()->willReturn($newRoute->reveal());
        $newRoute->setLocale('de')->shouldBeCalled()->willReturn($newRoute->reveal());

        $conflict = $this->prophesize(RouteInterface::class);
        $conflict->addHistory($route->reveal())->shouldBeCalled()->willReturn($conflict->reveal());
        $conflict->getPath()->willReturn('/test-2');

        $route->setTarget($conflict->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([]);

        $this->entity->setRoute($conflict->reveal())->shouldBeCalled();

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test-2');
        $this->routeRepository->createNew()->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($conflict->reveal());

        $this->assertEquals($conflict->reveal(), $this->manager->update($this->entity->reveal()));
    }

    public function testUpdateMultipleHistory()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->setEntityClass(get_class($this->entity->reveal()))->shouldBeCalled()
            ->willReturn($newRoute->reveal());
        $newRoute->setPath('/test-2')->shouldBeCalled()->will(
            function () use ($newRoute) {
                $newRoute->getPath()->willReturn('/test-2');

                return $newRoute->reveal();
            }
        );
        $newRoute->setEntityId('1')->shouldBeCalled()->willReturn($newRoute->reveal());
        $newRoute->setLocale('de')->shouldBeCalled()->willReturn($newRoute->reveal());
        $newRoute->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $historyRoute1 = $this->prophesize(RouteInterface::class);
        $historyRoute1->setTarget($newRoute->reveal())->shouldBeCalled();
        $historyRoute1->getPath()->willReturn('/history-1');
        $newRoute->addHistory($historyRoute1->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $historyRoute2 = $this->prophesize(RouteInterface::class);
        $historyRoute2->setTarget($newRoute->reveal())->shouldBeCalled();
        $historyRoute2->getPath()->willReturn('/history-2');
        $newRoute->addHistory($historyRoute2->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($newRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([$historyRoute1->reveal(), $historyRoute2->reveal()]);

        $this->entity->setRoute($newRoute->reveal())->shouldBeCalled();

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test-2');
        $this->routeRepository->createNew()->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $this->assertEquals($newRoute->reveal(), $this->manager->update($this->entity->reveal()));
    }

    public function testUpdateRestore()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->setEntityClass(get_class($this->entity->reveal()))->shouldBeCalled()
            ->willReturn($newRoute->reveal());
        $newRoute->setPath('/test-2')->shouldBeCalled()->will(
            function () use ($newRoute) {
                $newRoute->getPath()->willReturn('/test-2');

                return $newRoute->reveal();
            }
        );
        $newRoute->setEntityId('1')->shouldBeCalled()->willReturn($newRoute->reveal());
        $newRoute->setLocale('de')->shouldBeCalled()->willReturn($newRoute->reveal());

        $historyRoute1 = $this->prophesize(RouteInterface::class);
        $historyRoute1->removeTarget()->shouldBeCalled()->willReturn($historyRoute1->reveal());
        $historyRoute1->setHistory(false)->shouldBeCalled()->willReturn($historyRoute1->reveal());
        $historyRoute1->getPath()->willReturn('/test-2');
        $historyRoute1->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($historyRoute1->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([$historyRoute1->reveal()]);

        $this->entity->setRoute($historyRoute1->reveal())->shouldBeCalled();

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test-2');
        $this->routeRepository->createNew()->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($historyRoute1->reveal());

        $result = $this->manager->update($this->entity->reveal());
    }

    public function testUpdateNoChange()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');

        $this->entity->getRoute()->willReturn($route->reveal());

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test');

        $this->assertEquals($route->reveal(), $this->manager->update($this->entity->reveal()));
    }

    public function testUpdateNoRoute()
    {
        $this->setExpectedException(RouteNotCreatedException::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->getId()->willReturn('1');

        $this->routeGenerator->generate($this->entity->reveal(), '/{title}')->willReturn('/test');

        $this->manager->update($this->entity->reveal());
    }
}
