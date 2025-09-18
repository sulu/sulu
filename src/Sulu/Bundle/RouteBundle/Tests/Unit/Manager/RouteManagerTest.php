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

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
use Sulu\Bundle\RouteBundle\Tests\Unit\TestRoutable;

class RouteManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ChainRouteGeneratorInterface>
     */
    private $chainRouteGenerator;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var ObjectProphecy<ConflictResolverInterface>
     */
    private $conflictResolver;

    /**
     * @var RouteManager
     */
    private $manager;

    /**
     * @var ObjectProphecy<RoutableInterface>
     */
    private $entity;

    protected function setUp(): void
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

    public function testCreate(): void
    {
        $route = $this->prophesize(RouteInterface::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal()));
    }

    public function testCreateInheritMapping(): void
    {
        $entity = new TestRoutableProxy();

        $route = $this->prophesize(RouteInterface::class);
        $this->chainRouteGenerator->generate($entity, null)->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($entity));
        $this->assertEquals($route->reveal(), $entity->getRoute());
    }

    public function testCreateWithRoutePath(): void
    {
        $route = $this->prophesize(RouteInterface::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test')->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($route->reveal());

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal(), '/test'));
    }

    public function testCreateWithRoutePathAndResolveConflictFalse(): void
    {
        $route = $this->prophesize(RouteInterface::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test')->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->shouldNotBeCalled();

        $this->assertEquals($route->reveal(), $this->manager->create($this->entity->reveal(), '/test', false));
    }

    public function testCreateWithRoutePathAndResolveConflictFalseNotUnique(): void
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getId()->willReturn(1);
        $route->getPath()->willReturn('/test');
        $route->getLocale()->willReturn('de');

        $loadedRoute = $this->prophesize(RouteInterface::class);
        $loadedRoute->getId()->willReturn(5);

        $this->entity->getId()->willReturn(3);
        $this->entity->getRoute()->willReturn(null);
        $this->entity->setRoute($route->reveal())->shouldNotBeCalled();

        $this->chainRouteGenerator->generate($this->entity->reveal(), '/test')->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->shouldNotBeCalled();
        $this->routeRepository->findByPath('/test', 'de')->willReturn($loadedRoute->reveal());

        $this->expectException(RouteIsNotUniqueException::class);

        $this->manager->create($this->entity->reveal(), '/test', false);
    }

    public function testCreateAlreadyExists(): void
    {
        $this->expectException(RouteAlreadyCreatedException::class);

        $route = $this->prophesize(RouteInterface::class);
        $this->entity->getRoute()->willReturn($route->reveal());
        $this->entity->getId()->willReturn('1');

        $this->manager->create($this->entity->reveal());
    }

    public function testCreateWithConflict(): void
    {
        $route = $this->prophesize(RouteInterface::class);
        $conflict = $this->prophesize(RouteInterface::class);

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)->willReturn($route->reveal());
        $this->conflictResolver->resolve($route->reveal())->willReturn($conflict->reveal());

        $this->assertEquals($conflict->reveal(), $this->manager->create($this->entity->reveal()));
    }

    public function testUpdate(): void
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

    public function testUpdateInheritMapping(): void
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

    public function testUpdateWithConflict(): void
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

    public function testUpdateMultipleHistory(): void
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

    public function testUpdateRestore(): void
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

    public function testUpdateNoChange(): void
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');

        $this->entity->getRoute()->willReturn($route->reveal());

        $this->chainRouteGenerator->generate($this->entity->reveal(), null)
            ->willReturn(new Route('/test'));

        $this->assertEquals($route->reveal(), $this->manager->update($this->entity->reveal()));
    }

    public function testUpdateNoRoute(): void
    {
        $this->expectException(RouteNotCreatedException::class);

        $this->entity->getRoute()->willReturn(null);
        $this->entity->getId()->willReturn('1');

        $this->chainRouteGenerator->generate($this->entity->reveal())->willReturn('/test');

        $this->manager->update($this->entity->reveal());
    }

    public function testUpdateWithPathAndResolveConflictFalse(): void
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

    public function testUpdateWithPathAndResolveConflictFalseNotUnique(): void
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

        $this->expectException(RouteIsNotUniqueException::class);

        $this->assertEquals($newRoute->reveal(), $this->manager->update($this->entity->reveal(), '/test-2', false));
    }

    public function testUpdateWithPathAndResolveConflictRestore(): void
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

    public function testCreateByAttributes(): void
    {
        $entityClass = TestRoutable::class;
        $entityId = '123-123-123';
        $locale = 'en';
        $path = '/test';

        $this->routeRepository->findByEntity($entityClass, $entityId, $locale)->willReturn(null);

        $route = $this->prophesize(RouteInterface::class);
        $route->setEntityClass($entityClass)->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityId($entityId)->shouldBeCalled()->willReturn($route->reveal());
        $route->setLocale($locale)->shouldBeCalled()->willReturn($route->reveal());
        $route->setPath($path)->shouldBeCalled()->willReturn($route->reveal());

        $this->routeRepository->createNew()->willReturn($route->reveal())->shouldBeCalled();
        $this->routeRepository->persist($route->reveal())->shouldBeCalled();

        $result = $this->manager->createOrUpdateByAttributes($entityClass, $entityId, $locale, $path, false);

        $this->assertEquals($route->reveal(), $result);
    }

    public function testCreateByAttributesResolveConflict(): void
    {
        $entityClass = TestRoutable::class;
        $entityId = '123-123-123';
        $locale = 'en';
        $path = '/test';

        $this->routeRepository->findByEntity($entityClass, $entityId, $locale)->willReturn(null);

        $route = $this->prophesize(RouteInterface::class);
        $route->setEntityClass($entityClass)->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityId($entityId)->shouldBeCalled()->willReturn($route->reveal());
        $route->setLocale($locale)->shouldBeCalled()->willReturn($route->reveal());
        $route->setPath($path)->shouldBeCalled()->willReturn($route->reveal());

        $this->routeRepository->createNew()->willReturn($route->reveal())->shouldBeCalled();

        $resolvedRoute = $this->prophesize(RouteInterface::class);
        $this->conflictResolver->resolve($route->reveal())->willReturn($resolvedRoute->reveal());

        $this->routeRepository->persist($resolvedRoute->reveal())->shouldBeCalled();

        $result = $this->manager->createOrUpdateByAttributes($entityClass, $entityId, $locale, $path, true);

        $this->assertEquals($resolvedRoute->reveal(), $result);
    }

    public function testUpdateByAttributesSamePath(): void
    {
        $entityClass = TestRoutable::class;
        $entityId = '123-123-123';
        $locale = 'en';
        $path = '/test';

        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->shouldBeCalled()->willReturn($path);

        $this->routeRepository->findByEntity($entityClass, $entityId, $locale)->willReturn($route->reveal());
        $this->routeRepository->createNew()->shouldNotBeCalled();

        $result = $this->manager->createOrUpdateByAttributes($entityClass, $entityId, $locale, $path);

        $this->assertEquals($route->reveal(), $result);
    }

    public function testUpdateByAttributesNewPath(): void
    {
        $entityClass = TestRoutable::class;
        $entityId = '123-123-123';
        $locale = 'en';
        $path = '/test';

        $oldRoute = $this->prophesize(RouteInterface::class);
        $oldRoute->getPath()->shouldBeCalled()->willReturn('/test2');

        $this->routeRepository->findByEntity($entityClass, $entityId, $locale)->willReturn($oldRoute->reveal());

        $route = $this->prophesize(RouteInterface::class);
        $route->setEntityClass($entityClass)->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityId($entityId)->shouldBeCalled()->willReturn($route->reveal());
        $route->setLocale($locale)->shouldBeCalled()->willReturn($route->reveal());
        $route->setPath($path)->shouldBeCalled()->willReturn($route->reveal());
        $route->getPath()->willReturn($path);

        $this->routeRepository->createNew()->willReturn($route->reveal())->shouldBeCalled();

        $oldRoute->setHistory(true)->shouldBeCalled()->willReturn($oldRoute->reveal());
        $oldRoute->setTarget($route->reveal())->shouldBeCalled()->willReturn($oldRoute->reveal());

        $historyRoute1 = $this->prophesize(RouteInterface::class);
        $historyRoute1->setTarget($route->reveal())->shouldBeCalled();
        $historyRoute1->getPath()->willReturn('/history-1');
        $route->addHistory($historyRoute1->reveal())->shouldBeCalled()->willReturn($route->reveal());

        $historyRoute2 = $this->prophesize(RouteInterface::class);
        $historyRoute2->setTarget($route->reveal())->shouldBeCalled();
        $historyRoute2->getPath()->willReturn('/history-2');
        $route->addHistory($historyRoute2->reveal())->shouldBeCalled()->willReturn($route->reveal());

        $route->addHistory($oldRoute->reveal())->shouldBeCalled()->willReturn($route->reveal());
        $oldRoute->getHistories()->willReturn([$historyRoute1->reveal(), $historyRoute2->reveal()]);

        $this->routeRepository->persist($route->reveal())->shouldBeCalled();

        $result = $this->manager->createOrUpdateByAttributes($entityClass, $entityId, $locale, $path, false);

        $this->assertEquals($route->reveal(), $result);
    }

    public function testUpdateByAttributesNewPathResolveConflict(): void
    {
        $entityClass = TestRoutable::class;
        $entityId = '123-123-123';
        $locale = 'en';
        $path = '/test';

        $oldRoute = $this->prophesize(RouteInterface::class);
        $oldRoute->getPath()->shouldBeCalled()->willReturn('/test2');

        $this->routeRepository->findByEntity($entityClass, $entityId, $locale)->willReturn($oldRoute->reveal());

        $route = $this->prophesize(RouteInterface::class);
        $route->setEntityClass($entityClass)->shouldBeCalled()->willReturn($route->reveal());
        $route->setEntityId($entityId)->shouldBeCalled()->willReturn($route->reveal());
        $route->setLocale($locale)->shouldBeCalled()->willReturn($route->reveal());
        $route->setPath($path)->shouldBeCalled()->willReturn($route->reveal());

        $this->routeRepository->createNew()->willReturn($route->reveal())->shouldBeCalled();

        $resolvedRoute = $this->prophesize(RouteInterface::class);
        $resolvedRoute->getPath()->willReturn($path . '-1');
        $this->conflictResolver->resolve($route->reveal())->willReturn($resolvedRoute->reveal());

        $oldRoute->setHistory(true)->shouldBeCalled()->willReturn($oldRoute->reveal());
        $oldRoute->setTarget($resolvedRoute->reveal())->shouldBeCalled()->willReturn($oldRoute->reveal());

        $historyRoute1 = $this->prophesize(RouteInterface::class);
        $historyRoute1->setTarget($resolvedRoute->reveal())->shouldBeCalled();
        $historyRoute1->getPath()->willReturn('/history-1');
        $resolvedRoute->addHistory($historyRoute1->reveal())->shouldBeCalled()->willReturn($resolvedRoute->reveal());

        $historyRoute2 = $this->prophesize(RouteInterface::class);
        $historyRoute2->setTarget($resolvedRoute->reveal())->shouldBeCalled();
        $historyRoute2->getPath()->willReturn('/history-2');
        $resolvedRoute->addHistory($historyRoute2->reveal())->shouldBeCalled()->willReturn($resolvedRoute->reveal());

        $resolvedRoute->addHistory($oldRoute->reveal())->shouldBeCalled()->willReturn($resolvedRoute->reveal());
        $oldRoute->getHistories()->willReturn([$historyRoute1->reveal(), $historyRoute2->reveal()]);

        $this->routeRepository->persist($resolvedRoute->reveal())->shouldBeCalled();

        $result = $this->manager->createOrUpdateByAttributes($entityClass, $entityId, $locale, $path, true);

        $this->assertEquals($resolvedRoute->reveal(), $result);
    }
}

class TestRoutableProxy extends TestRoutable
{
}
