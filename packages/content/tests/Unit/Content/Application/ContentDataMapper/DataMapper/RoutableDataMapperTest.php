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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentDataMapper\DataMapper\RoutableDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Uid\Uuid;

class RoutableDataMapperTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    protected function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
    }

    protected function createRouteDataMapperInstance(TypedFormMetadata $typedFormMetadata): RoutableDataMapper
    {
        $container = new Container();
        $container->set('form', new class($typedFormMetadata) implements MetadataProviderInterface {
            public function __construct(private readonly TypedFormMetadata $typedFormMetadata)
            {
            }

            public function getMetadata(string $key, string $locale, array $metadataOptions): TypedFormMetadata
            {
                return $this->typedFormMetadata;
            }
        });
        $metadataProviderRegistry = new MetadataProviderRegistry($container);

        return new RoutableDataMapper(
            $this->routeRepository->reveal(),
            $metadataProviderRegistry,
        );
    }

    public function testMapNoRoutableInterface(): void
    {
        $data = [];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(new TypedFormMetadata());
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(new TypedFormMetadata());
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(new TypedFormMetadata());

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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(new TypedFormMetadata());
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithTextLine());
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute());
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute());
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

        $this->routeRepository->add(Argument::any())->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute());
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapPageTreeRouteProperty(): void
    {
        $uuid = Uuid::v4()->toRfc4122();

        $data = [
            'url' => [
                'page' => [
                    'uuid' => $uuid,
                    'path' => '/parent',
                ],
                'suffix' => '/test',
            ],
        ];

        $example = new Example();
        static::setPrivateProperty($example, 'id', 1);
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage('draft');
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setTemplateKey('default');
        $localizedDimensionContent->setStage('draft');
        $localizedDimensionContent->setLocale('en');

        $this->routeRepository->add(Argument::any())->shouldBeCalled();
        $parentRoute = new Route('pages', $uuid, 'en', '/parent', 'sulu-io', null);
        $this->routeRepository->findOneBy([
            'resourceKey' => 'pages',
            'resourceId' => $uuid,
            'locale' => 'en',
        ])
            ->willReturn($parentRoute)
            ->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithPageTreeRoute());
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/parent/test', $localizedDimensionContent->getRoute()?->getSlug());
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

        $this->routeRepository->findOneBy([
            'locale' => 'en',
            'resourceKey' => 'examples',
            'resourceId' => '1',
        ])
            ->willReturn($route)
            ->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute());
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

        $this->routeRepository->add(Argument::any())->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute());
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute('route'));
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

        $this->routeRepository->add(Argument::any())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance($this->createTypedFormMetadataWithRoute());
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('/test', $localizedDimensionContent->getRoute()?->getSlug());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    private function createTypedFormMetadataWithRoute(string $propertyName = 'url'): TypedFormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setTitle('Default', 'en');
        $formMetadata->setKey('default');

        $routeProperty = new FieldMetadata($propertyName);
        $routeProperty->setMultilingual(true);
        $routeProperty->setType('route');

        $formMetadata->addItem($routeProperty);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
        $typedFormMetadata->setDefaultType('default');

        return $typedFormMetadata;
    }

    private function createTypedFormMetadataWithPageTreeRoute(string $propertyName = 'url'): TypedFormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setTitle('Default', 'en');
        $formMetadata->setKey('default');

        $routeProperty = new FieldMetadata($propertyName);
        $routeProperty->setMultilingual(true);
        $routeProperty->setType('page_tree_route');

        $formMetadata->addItem($routeProperty);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
        $typedFormMetadata->setDefaultType('default');

        return $typedFormMetadata;
    }

    private function createTypedFormMetadataWithTextLine(string $propertyName = 'url'): TypedFormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setTitle('Default', 'en');
        $formMetadata->setKey('default');

        $routeProperty = new FieldMetadata($propertyName);
        $routeProperty->setMultilingual(true);
        $routeProperty->setType('text_line');

        $formMetadata->addItem($routeProperty);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
        $typedFormMetadata->setDefaultType('default');

        return $typedFormMetadata;
    }
}
