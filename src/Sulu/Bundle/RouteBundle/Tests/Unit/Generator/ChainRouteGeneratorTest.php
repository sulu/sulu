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

use Prophecy\Argument;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\MissingClassMappingConfigurationException;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class ChainRouteGeneratorTest extends \PHPUnit_Framework_TestCase
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
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var RoutableInterface
     */
    private $entity;

    public function setUp()
    {
        $this->entity = $this->prophesize(RoutableInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->mappings = [
            get_class($this->entity->reveal()) => [
                'generator' => 'route_generator',
                'options' => [
                    'route_schema' => '/{title}',
                ],
            ],
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

    public function testGenerate()
    {
        $this->routeGenerator->generate($this->entity->reveal(), ['route_schema' => '/{title}'])->willReturn('/test');

        $result = $this->chainRouteGenerator->generate($this->entity->reveal());
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(get_class($this->entity->reveal()), $result->getEntityClass());
    }

    public function testGenerateWithPath()
    {
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $result = $this->chainRouteGenerator->generate($this->entity->reveal(), '/test');
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(get_class($this->entity->reveal()), $result->getEntityClass());
    }

    public function testGenerateInheritMapping()
    {
        $entity = new TestRoutableProxy();
        $this->routeGenerator->generate($entity, ['route_schema' => '/{title}'])->willReturn('/test');

        $result = $this->chainRouteGenerator->generate($entity);
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(TestRoutable::class, $result->getEntityClass());
    }

    public function testGenerateInheritMappingWithPath()
    {
        $entity = new TestRoutableProxy();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $result = $this->chainRouteGenerator->generate($entity, '/test');
        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(TestRoutable::class, $result->getEntityClass());
    }

    public function testGenerateNoMapping()
    {
        $this->setExpectedException(MissingClassMappingConfigurationException::class);
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
