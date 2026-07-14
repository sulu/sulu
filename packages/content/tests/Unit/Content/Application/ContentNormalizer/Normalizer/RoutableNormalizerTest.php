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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentNormalizer\Normalizer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\ContentNormalizer\Normalizer\RoutableNormalizer;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Domain\Model\Route;

class RoutableNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContainerInterface>
     */
    private ObjectProphecy $container;

    private RoutableNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->has('form')->willReturn(true);
        $this->normalizer = new RoutableNormalizer(
            new MetadataProviderRegistry($this->container->reveal()),
        );
    }

    public function testIgnoredAttributesNotImplementRoutableInterface(): void
    {
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame([], $this->normalizer->getIgnoredAttributes($object->reveal()));
    }

    public function testIgnoredAttributes(): void
    {
        $object = $this->prophesize(RoutableInterface::class);

        $this->assertSame(
            ['resourceId', 'route'],
            $this->normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementRoutableInterface(): void
    {
        $object = $this->prophesize(\stdClass::class);
        $data = ['property1' => 'value-1', 'property2' => 'value-2'];

        $this->assertSame($data, $this->normalizer->enhance($object->reveal(), $data));
    }

    public function testEnhanceRoutableButNotTemplate(): void
    {
        $object = $this->prophesize(DimensionContentInterface::class)
            ->willImplement(RoutableInterface::class);
        $data = ['property1' => 'value-1'];

        $this->assertSame($data, $this->normalizer->enhance($object->reveal(), $data));
    }

    public function testEnhanceTemplateWithoutLocale(): void
    {
        $object = $this->prophesize(DimensionContentInterface::class)
            ->willImplement(RoutableInterface::class)
            ->willImplement(TemplateInterface::class);
        $object->getLocale()->willReturn(null);
        $data = ['property1' => 'value-1'];

        $this->assertSame($data, $this->normalizer->enhance($object->reveal(), $data));
    }

    public function testEnhanceTemplateWithoutTemplateKey(): void
    {
        $object = $this->prophesize(DimensionContentInterface::class)
            ->willImplement(RoutableInterface::class)
            ->willImplement(TemplateInterface::class);
        $object->getLocale()->willReturn('en');
        $object->getTemplateKey()->willReturn(null);
        $data = ['property1' => 'value-1'];

        $this->assertSame($data, $this->normalizer->enhance($object->reveal(), $data));
    }

    public function testEnhanceFillsRouteUrl(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $object = $this->createDimensionContent('en', 'default', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'route'),
            'title' => $this->createField('title', 'text_line'),
        ]);

        $result = $this->normalizer->enhance($object, ['title' => 'Page title']);

        $this->assertSame(
            ['title' => 'Page title', 'url' => '/my-page'],
            $result
        );
    }

    public function testEnhanceFillsPageTreeRouteUrl(): void
    {
        $parentRoute = new Route('pages', 'parent-uuid', 'en', '/parent');
        $route = new Route('pages', 'child-uuid', 'en', '/parent/child', null, $parentRoute);
        $object = $this->createDimensionContent('en', 'default', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'page_tree_route'),
        ]);

        $result = $this->normalizer->enhance($object, []);

        $this->assertSame(
            [
                'url' => [
                    'page' => ['uuid' => 'parent-uuid', 'path' => '/parent'],
                    'suffix' => '/child',
                ],
            ],
            $result
        );
    }

    public function testEnhanceFillsPageTreeRouteUrlWhenParentIsHomepage(): void
    {
        $parentRoute = new Route('pages', 'homepage-uuid', 'en', '/');
        $route = new Route('pages', 'child-uuid', 'en', '/test123', null, $parentRoute);
        $object = $this->createDimensionContent('en', 'default', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'page_tree_route'),
        ]);

        $result = $this->normalizer->enhance($object, []);

        $this->assertSame(
            [
                'url' => [
                    'page' => ['uuid' => 'homepage-uuid', 'path' => '/'],
                    'suffix' => '/test123',
                ],
            ],
            $result
        );
    }

    public function testEnhanceDoesNotAddUrlWhenRouteIsNull(): void
    {
        $object = $this->createDimensionContent('en', 'default');

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'route'),
            'title' => $this->createField('title', 'text_line'),
        ]);

        $result = $this->normalizer->enhance($object, ['title' => 'Page title']);

        $this->assertSame(['title' => 'Page title'], $result);
        $this->assertArrayNotHasKey('url', $result);
    }

    public function testEnhanceAddsUrlWhenRouteSlugIsEmptyString(): void
    {
        $route = new Route('examples', '1', 'en', '');
        $object = $this->createDimensionContent('en', 'default', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'route'),
            'title' => $this->createField('title', 'text_line'),
        ]);

        $result = $this->normalizer->enhance($object, ['title' => 'Page title']);

        $this->assertArrayHasKey('url', $result);
        $this->assertSame('', $result['url']);
    }

    public function testEnhanceDoesNotAddPageTreeRouteUrlWhenNoParentRoute(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        // route has no parent — resolvePageTreeRoute returns null
        $object = $this->createDimensionContent('en', 'default', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'page_tree_route'),
        ]);

        $result = $this->normalizer->enhance($object, []);

        $this->assertSame([], $result);
        $this->assertArrayNotHasKey('url', $result);
    }

    public function testEnhanceLeavesNonRouteFieldsUntouched(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $object = $this->createDimensionContent('en', 'default', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'title' => $this->createField('title', 'text_line'),
        ]);

        $result = $this->normalizer->enhance($object, ['title' => 'Original']);

        $this->assertSame(['title' => 'Original'], $result);
    }

    public function testEnhanceWithoutTypedFormMetadataReturnsDataUnchanged(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $object = $this->createDimensionContent('en', 'default', $route);

        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('example', 'en', [])
            ->willReturn($this->prophesize(MetadataInterface::class)->reveal());
        $this->container->get('form')
            ->willReturn($formMetadataProvider->reveal());

        $data = ['title' => 'Page title'];
        $this->assertSame($data, $this->normalizer->enhance($object, $data));
    }

    public function testEnhanceWithUnknownTemplateKeyReturnsDataUnchanged(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $object = $this->createDimensionContent('en', 'unknown', $route);

        $this->primeFormMetadata('example', 'en', 'default', [
            'url' => $this->createField('url', 'route'),
        ]);

        $data = ['title' => 'Page title'];
        $this->assertSame($data, $this->normalizer->enhance($object, $data));
    }

    private function createField(string $name, string $type): FieldMetadata
    {
        $field = new FieldMetadata($name);
        $field->setType($type);

        return $field;
    }

    /**
     * @param array<string, FieldMetadata> $fields
     */
    private function primeFormMetadata(string $templateType, string $locale, string $templateKey, array $fields): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setItems($fields);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($templateKey, $formMetadata);

        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata($templateType, $locale, [])
            ->willReturn($typedFormMetadata);

        $this->container->get('form')
            ->willReturn($formMetadataProvider->reveal());
    }

    private function createDimensionContent(string $locale, string $templateKey, ?Route $route = null): ExampleDimensionContent
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setTemplateKey($templateKey);
        if (null !== $route) {
            $dimensionContent->setRoute($route);
        }

        return $dimensionContent;
    }
}
