<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Manager;

use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\RouteIsNotUniqueException;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteAlreadyCreatedException;
use Sulu\Bundle\RouteBundle\Manager\RouteManager;
use Sulu\Bundle\RouteBundle\Manager\RouteNotCreatedException;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class RouteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

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
        $this->chainRouteGenerator = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->manager = new RouteManager(
            $this->chainRouteGenerator->reveal(),
            $this->conflictResolver->reveal(),
            $this->routeRepository->reveal()
        );
    }

    public function testCreate()
    {
        $route = $this->prophesize(RouteInterface::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal()));
    }

    public function testCreateInheritMapping()
    {
        $entity = new TestRoutableProxy();

        $route = $this->prophesize(RouteInterface::class);
        $this->chainRouteGenerator->generate($entity, null)->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($entity));
        $this->assertEquals($route->reveal(), $entity->getRoute());
    }

    public function testCreateWithRoutePath()
    {
        $route = $this->prophesize(RouteInterface::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test')->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal(), '/test'));
    }

    public function testCreateWithRoutePathAndResolveConflictFalse()
    {
        $route = $this->prophesize(RouteInterface::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test')->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->shouldNotBeCalled();

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal(), '/test', false));
    }

    public function testCreateWithRoutePathAndResolveConflictFalseNotUnique()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getId()->willReturn(1);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');

        $loadedRoute = $this->prophesize(RouteInterface::class);
        $loadedRoute->getId()->willReturn(5);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldNotBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test')->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->shouldNotBeCalled();
        $this->routeRepository->findByPath('/test', 'de')->willReturn($loadedRoute->reveal());

        $this->setExpectedException(RouteIsNotUniqueException::class);

        $this->manager->create($this->entity->reveal(), '/test', false);
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
        $conflict = $this->prophesize(RouteInterface::class);

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($route->reveal());
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
        $newRoute->getPath()->willReturn('/test-2');
        $newRoute->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($newRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([]);

        $this->entity->setRoute($newRoute->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test-2')->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $this->assertEquals($newRoute->reveal(), $this->manager->update($this->entity->reveal(), '/test-2'));
    }

    public function testUpdateInheritMapping()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());

        $entity = new TestRoutableProxy($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->getPath()->willReturn('/test-2');
        $newRoute->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($newRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([]);

        $this->chainRouteGenerator->generate($entity, null)->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $this->assertEquals($newRoute->reveal(), $this->manager->update($entity));
        $this->assertEquals($newRoute->reveal(), $entity->getRoute());
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
        $newRoute->getPath()->willReturn('/test-2');

        $conflict = $this->prophesize(RouteInterface::class);
        $conflict->addHistory($route->reveal())->shouldBeCalled()->willReturn($conflict->reveal());
        $conflict->getPath()->willReturn('/test-2');

        $route->setTarget($conflict->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([]);

        $this->entity->setRoute($conflict->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($newRoute->reveal());
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
        $newRoute->getPath()->willReturn('/test-2');
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

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($newRoute->reveal());
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
        $newRoute->getPath()->willReturn('/test-2');

        $historyRoute1 = $this->prophesize(RouteInterface::class);
        $historyRoute1->removeTarget()->shouldBeCalled()->willReturn($historyRoute1->reveal());
        $historyRoute1->setHistory(false)->shouldBeCalled()->willReturn($historyRoute1->reveal());
        $historyRoute1->getPath()->willReturn('/test-2');
        $historyRoute1->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($historyRoute1->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([$historyRoute1->reveal()]);

        $this->entity->setRoute($historyRoute1->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldBeCalled()->willReturn($historyRoute1->reveal());

        $this->manager->update($this->entity->reveal());
    }

    public function testUpdateNoChange()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');

        $this->entity->getRoute()->willReturn($route->reveal());

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)
            ->willReturn(new Route('/test'));

        $this->assertEquals($route->reveal(), $this->manager->update($this->entity->reveal()));
    }

    public function testUpdateNoRoute()
    {
        $this->setExpectedException(RouteNotCreatedException::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->getId()->willReturn('1');

        $this->chainRouteGenerator->generate($this->entity->reveal())->willReturn('/test');

        $this->manager->update($this->entity->reveal());
    }

    public function testUpdateWithPathAndResolveConflictFalse()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');
        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->getPath()->willReturn('/test-2');
        $newRoute->getLocale()->willReturn('de');
        $newRoute->addHistory($route->reveal())->shouldBeCalled()->willReturn($newRoute->reveal());

        $route->setTarget($newRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $route->getHistories()->willReturn([]);

        $this->entity->setRoute($newRoute->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test-2')->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldNotBeCalled()->willReturn($newRoute->reveal());

        $this->assertEquals($newRoute->reveal(), $this->manager->update($this->entity->reveal(), '/test-2', false));
    }

    public function testUpdateWithPathAndResolveConflictFalseNotUnique()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');
        $route->getEntityClass()->willReturn('TestEntity');
        $route->getEntityId()->willReturn(1);

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->getId()->willReturn(1);
        $newRoute->getPath()->willReturn('/test-2');
        $newRoute->getLocale()->willReturn('de');
        $newRoute->getEntityClass()->willReturn('TestEntity');
        $newRoute->getEntityId()->willReturn(1);

        $loadedRoute = $this->prophesize(RouteInterface::class);
        $loadedRoute->getId()->willReturn(5);
        $loadedRoute->getEntityClass()->willReturn('TestEntity');
        $loadedRoute->getEntityId()->willReturn(2);

        $this->entity->setRoute($newRoute->reveal())->shouldNotBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test-2')->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldNotBeCalled()->willReturn($newRoute->reveal());
        $this->routeRepository->findByPath('/test-2', 'de')->willReturn($loadedRoute->reveal());

        $this->setExpectedException(RouteIsNotUniqueException::class);

        $this->assertEquals($newRoute->reveal(), $this->manager->update($this->entity->reveal(), '/test-2', false));
    }

    public function testUpdateWithPathAndResolveConflictRestore()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');
        $route->getEntityClass()->willReturn('TestEntity');
        $route->getEntityId()->willReturn(1);

        $this->entity->getId()->willReturn('1');
        $this->entity->getLocale()->willReturn('de');
        $this->entity->getRoute()->willReturn($route->reveal());

        $newRoute = $this->prophesize(RouteInterface::class);
        $newRoute->getId()->willReturn(1);
        $newRoute->getPath()->willReturn('/test-2');
        $newRoute->getLocale()->willReturn('de');
        $newRoute->getEntityClass()->willReturn('TestEntity');
        $newRoute->getEntityId()->willReturn(1);

        $loadedRoute = $this->prophesize(RouteInterface::class);
        $loadedRoute->getId()->willReturn(5);
        $loadedRoute->getEntityClass()->willReturn('TestEntity');
        $loadedRoute->getEntityId()->willReturn(1);
        $loadedRoute->getPath()->willReturn('/test-2');

        $this->entity->setRoute($newRoute->reveal())->shouldNotBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test-2')->willReturn($newRoute->reveal());
        $this->conflictResolver->resolve($newRoute->reveal())->shouldNotBeCalled()->willReturn($newRoute->reveal());
        $this->routeRepository->findByPath('/test-2', 'de')->willReturn($loadedRoute->reveal());

        $route->setHistory(true)->shouldBeCalled()->willReturn($route->reveal());
        $route->setTarget($loadedRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());

        $loadedRoute->addHistory($route->reveal())->shouldBeCalled();
        $route->getHistories()->willReturn([]);
        $this->entity->setRoute($loadedRoute->reveal())->shouldBeCalled();

        $this->assertEquals($loadedRoute->reveal(), $this->manager->update($this->entity->reveal(), '/test-2', false));
    }
}

class TestRoutable implements RoutableInterface
{
    /**
     * @var RouteInterface
     */
    private $route;

    /**
     * @param RouteInterface $route
     */
    public function __construct(RouteInterface $route = null)
    {
        $this->route = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * {@inheritdoc}
     */
    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return 'de';
    }
}

class TestRoutableProxy extends TestRoutable
{
}
