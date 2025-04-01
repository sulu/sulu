<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Content\Application\ContentDataMapper\DataMapper\RoutableDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class RoutableDataMapperTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureMetadataFactory;

    protected function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
    }

    protected function createRouteDataMapperInstance(): RoutableDataMapper
    {
        return new RoutableDataMapper(
            $this->routeRepository->reveal(),
            $this->structureMetadataFactory->reveal(),
        );
    }

    public function testMapNoRoutableInterface(): void
    {
        $data = [];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->structureMetadataFactory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoTemplateInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LocalizedDimensionContent needs to extend the TemplateInterface.');

        $data = [];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $unlocalizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);

        $this->structureMetadataFactory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoTemplateGiven(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LocalizedDimensionContent should return the a template.');

        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();

        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);
    }

    public function testMapNoMetadata(): void
    {
        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn(null);
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
        $this->assertNull($localizedDimensionContent->getRoute());
    }

    public function testMapNoRouteProperty(): void
    {
        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createTextLineStructureMetadata());
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
        $this->assertNull($localizedDimensionContent->getRoute());
    }

    public function testMapNoLocale(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a LocalizedDimensionContent with a locale.');

        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertNull($localizedDimensionContent->getRoute());
    }

    public function testMapNoRoutePropertyValue(): void
    {
        $data = [];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
        $this->assertNull($localizedDimensionContent->getRoute());
    }

    public function testMapRouteProperty(): void
    {
        $data = [
            'url' => '/test',
        ];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('draft');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('draft');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeRepository->add(Argument::any())->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapRoutePropertyLive(): void
    {
        $data = [
            'url' => '/test',
        ];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('live');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('live');
        $localizedDimensionContent->setLocale('en');

        $route = new Route(Example::RESOURCE_KEY, '1', 'en', '/test', null, null);

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeRepository->findOneBy([
            'locale' => 'en',
            'resourceKey' => 'examples',
            'resourceId' => '1',
        ])
            ->willReturn($route)
            ->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNoResourceId(): void
    {
        $data = [
            'url' => '/test',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('draft');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('draft');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeRepository->add(Argument::any())->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([], $localizedDimensionContent->getTemplateData());
        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
    }

    public function testMapRoutePropertyFalseName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected a property with the name "url" but "route" given.');

        $data = [
            'route' => '/test',
        ];
        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('draft');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('draft');
        $localizedDimensionContent->setLocale('en');

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata('route'));
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapWithNoUrlButOldUrl(): void
    {
        $data = [];

        $route = new Route(
            ExampleDimensionContent::getResourceKey(),
            '1',
            'en',
            '/test',
            null,
            null,
        );

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('draft');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('draft');
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setRoute($route);

        $this->structureMetadataFactory->getStructureMetadata('example', 'default')
            ->shouldBeCalled()
            ->willReturn($this->createRouteStructureMetadata());
        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    private function createRouteStructureMetadata(string $propertyName = 'url'): StructureMetadata
    {
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn($propertyName);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getProperties()->willReturn([
            $property->reveal(),
        ])->shouldBeCalled();

        return $structureMetadata->reveal();
    }

    private function createTextLineStructureMetadata(): StructureMetadata
    {
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('text_line');
        $property->getName()->willReturn('url');

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getProperties()->willReturn([
            $property->reveal(),
        ])->shouldBeCalled();

        return $structureMetadata->reveal();
    }
}
