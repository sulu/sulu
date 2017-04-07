<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Generator;

use Prophecy\Argument;
use Sulu\Bundle\RouteBundle\Exception\MissingClassMappingConfigurationException;
use Sulu\Bundle\RouteBundle\Generator\GeneratedRoute;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorPool;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorPoolInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class RouteGeneratorPoolTest extends \PHPUnit_Framework_TestCase
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
     * @var RouteGeneratorPoolInterface
     */
    private $routeGeneratorPool;

    /**
     * @var RoutableInterface
     */
    private $entity;

    public function setUp()
    {
        $this->entity = $this->prophesize(RoutableInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);

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

        $this->routeGeneratorPool = new RouteGeneratorPool(
            $this->mappings,
            ['route_generator' => $this->routeGenerator->reveal()]
        );
    }

    public function testGenerate()
    {
        $this->routeGenerator->generate($this->entity->reveal(), ['route_schema' => '/{title}'])->willReturn('/test');

        $result = $this->routeGeneratorPool->generate($this->entity->reveal());
        $this->assertInstanceOf(GeneratedRoute::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(get_class($this->entity->reveal()), $result->getEntityClass());
    }

    public function testGenerateWithPath()
    {
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $result = $this->routeGeneratorPool->generate($this->entity->reveal(), '/test');
        $this->assertInstanceOf(GeneratedRoute::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(get_class($this->entity->reveal()), $result->getEntityClass());
    }

    public function testGenerateInheritMapping()
    {
        $entity = new TestRoutableProxy();
        $this->routeGenerator->generate($entity, ['route_schema' => '/{title}'])->willReturn('/test');

        $result = $this->routeGeneratorPool->generate($entity);
        $this->assertInstanceOf(GeneratedRoute::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(TestRoutable::class, $result->getEntityClass());
    }

    public function testGenerateInheritMappingWithPath()
    {
        $entity = new TestRoutableProxy();
        $this->routeGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $result = $this->routeGeneratorPool->generate($entity, '/test');
        $this->assertInstanceOf(GeneratedRoute::class, $result);
        $this->assertEquals('/test', $result->getPath());
        $this->assertEquals(TestRoutable::class, $result->getEntityClass());
    }

    public function testGenerateNoMapping()
    {
        $this->setExpectedException(MissingClassMappingConfigurationException::class);
        $entity = $this->prophesize(RoutableInterface::class);

        $this->routeGeneratorPool->generate($entity->reveal());
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
