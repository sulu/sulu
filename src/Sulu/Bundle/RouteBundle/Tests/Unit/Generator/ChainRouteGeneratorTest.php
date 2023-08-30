<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\MissingClassMappingConfigurationException;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class ChainRouteGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    private $mappings;

    /**
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private $routeGenerator;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var RoutableInterface
     */
    private $entity;

    public function setUp(): void
    {
        $this->entity = new TestRoutable();
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->mappings = [
            TestRoutable::class => [
                'generator' => 'route_generator',
                'options' => [
                    'route_schema' => '/{title}',
                ],
            ],
        ];

        $this->routeRepository->createNew()->willReturn(new Route());

        $this->chainRouteGenerator = new ChainRouteGenerator(
            $this->mappings,
            ['route_generator' => $this->routeGenerator->reveal()],
            $this->routeRepository->reveal()
        );
    }

    public function testGenerate(): void
    {
        $this->routeGenerator->generate($this->entity, ['route_schema' => '/{title}'])->willReturn('/test');

        $result = $this->chainRouteGenerator->generate($this->entity);
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(\get_class($this->entity), $result->getEntityClass());
    }

    public function testGenerateWithPath(): void
    {
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $result = $this->chainRouteGenerator->generate($this->entity, '/test');
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(\get_class($this->entity), $result->getEntityClass());
    }

    public function testGenerateInheritMapping(): void
    {
        $entity = new TestRoutableProxy();
        $this->routeGenerator->generate($entity, ['route_schema' => '/{title}'])->willReturn('/test');

        $result = $this->chainRouteGenerator->generate($entity);
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(TestRoutable::class, $result->getEntityClass());
    }

    public function testGenerateInheritMappingWithPath(): void
    {
        $entity = new TestRoutableProxy();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $result = $this->chainRouteGenerator->generate($entity, '/test');
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(TestRoutable::class, $result->getEntityClass());
    }

    public function testGenerateNoMapping(): void
    {
        $this->expectException(MissingClassMappingConfigurationException::class);
        $entity = $this->prophesize(RoutableInterface::class);

        $this->chainRouteGenerator->generate($entity->reveal());
    }
}

class TestRoutable implements RoutableInterface
{
    /**
     * @var RouteInterface
     */
    private $route;

    public function __construct(?RouteInterface $route = null)
    {
        $this->route = $route;
    }

    public function getId()
    {
        return 1;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute(RouteInterface $route): void
    {
        $this->route = $route;
    }

    public function getLocale()
    {
        return 'de';
    }
}

class TestRoutableProxy extends TestRoutable
{
}
