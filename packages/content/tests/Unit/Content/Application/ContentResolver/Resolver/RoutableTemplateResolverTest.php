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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Resolver\RoutableTemplateResolver;
use Sulu\Content\Application\ContentResolver\Resolver\TemplateResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageTreeRoutePropertyResolver;
use Sulu\Route\Domain\Model\Route;

class RoutableTemplateResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveDelegatesToInnerForNonRoutable(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class)->reveal();
        $expected = ContentView::create(['inner' => 'value'], []);

        $inner = $this->prophesize(ResolverInterface::class);
        $inner->resolve($dimensionContent, null)->willReturn($expected)->shouldBeCalled();

        $resolver = new RoutableTemplateResolver(
            $inner->reveal(),
            $this->prophesize(MetadataProviderInterface::class)->reveal(),
        );

        $this->assertSame($expected, $resolver->resolve($dimensionContent));
    }

    public function testResolveFillsRouteFieldFromRouteEntity(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $dimensionContent = $this->createDimensionContent('en', 'default', $route);
        $dimensionContent->setTemplateData(['title' => 'Sulu']);

        $resolver = $this->createResolver(
            $this->createMetadataProvider('default', [
                'url' => $this->createField('url', 'route'),
                'title' => $this->createField('title', 'text_line'),
            ]),
        );

        $contentView = $resolver->resolve($dimensionContent);

        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ContentView::class, $content['url']);
        $this->assertSame('/my-page', $content['url']->getContent());
        $this->assertInstanceOf(ContentView::class, $content['title']);
        $this->assertSame('Sulu', $content['title']->getContent());
    }

    public function testResolveOverwritesStaleTemplateDataValueWithRouteSlug(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $dimensionContent = $this->createDimensionContent('en', 'default', $route);
        $dimensionContent->setTemplateData(['url' => '/stale-value']);

        $resolver = $this->createResolver(
            $this->createMetadataProvider('default', [
                'url' => $this->createField('url', 'route'),
            ]),
        );

        $contentView = $resolver->resolve($dimensionContent);
        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $urlView = $content['url'];
        $this->assertInstanceOf(ContentView::class, $urlView);
        $this->assertSame('/my-page', $urlView->getContent());
    }

    public function testResolveDoesNotOverwriteLinkUrlWithRouteSlug(): void
    {
        $route = new Route('examples', '1', 'en', '/external-link');
        $dimensionContent = $this->createDimensionContent('en', 'default', $route);
        $dimensionContent->setTemplateData(['url' => 'https://example.com']);
        $dimensionContent->setLinkData([
            'href' => 'https://example.com',
            'provider' => 'external',
        ]);

        $resolver = $this->createResolver(
            $this->createMetadataProvider('default', [
                'url' => $this->createField('url', 'route'),
            ]),
        );

        $contentView = $resolver->resolve($dimensionContent);
        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $urlView = $content['url'];
        $this->assertInstanceOf(ContentView::class, $urlView);
        $this->assertSame('https://example.com', $urlView->getContent());
    }

    public function testResolvePageTreeRouteFieldRunsThroughPropertyResolver(): void
    {
        $parentRoute = new Route('pages', 'parent-uuid', 'en', '/parent');
        $route = new Route('pages', 'child-uuid', 'en', '/parent/child', null, $parentRoute);

        $dimensionContent = $this->createDimensionContent('en', 'default', $route);
        $dimensionContent->setTemplateData([]);

        $resolver = $this->createResolver(
            $this->createMetadataProvider('default', [
                'url' => $this->createField('url', 'page_tree_route'),
            ]),
            new MetadataResolver(
                new PropertyResolverProvider(
                    new \ArrayIterator([
                        'default' => new DefaultPropertyResolver(),
                        'page_tree_route' => new PageTreeRoutePropertyResolver(),
                    ]),
                ),
            ),
        );

        $contentView = $resolver->resolve($dimensionContent);
        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);

        $urlView = $content['url'];
        $this->assertInstanceOf(ContentView::class, $urlView);
        $this->assertSame('/parent/child', $urlView->getContent());
    }

    public function testResolveHonoursPropertiesFilter(): void
    {
        $route = new Route('examples', '1', 'en', '/my-page');
        $dimensionContent = $this->createDimensionContent('en', 'default', $route);
        $dimensionContent->setTemplateData(['title' => 'Sulu']);

        $resolver = $this->createResolver(
            $this->createMetadataProvider('default', [
                'url' => $this->createField('url', 'route'),
                'title' => $this->createField('title', 'text_line'),
            ]),
        );

        $contentView = $resolver->resolve($dimensionContent, ['link' => 'url']);
        $this->assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('link', $content);
        $this->assertArrayNotHasKey('title', $content);
        $linkView = $content['link'];
        $this->assertInstanceOf(ContentView::class, $linkView);
        $this->assertSame('/my-page', $linkView->getContent());
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
    private function createMetadataProvider(string $templateKey, array $fields): MetadataProviderInterface
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setItems($fields);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($templateKey, $formMetadata);

        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(\Prophecy\Argument::cetera())
            ->willReturn($typedFormMetadata);

        return $provider->reveal();
    }

    private function createDimensionContent(string $locale, string $templateKey, Route $route): ExampleDimensionContent
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale($locale);
        $dimensionContent->setTemplateKey($templateKey);
        $dimensionContent->setRoute($route);

        return $dimensionContent;
    }

    private function createResolver(
        MetadataProviderInterface $metadataProvider,
        ?MetadataResolver $metadataResolver = null,
    ): RoutableTemplateResolver {
        $inner = new TemplateResolver(
            $metadataProvider,
            $metadataResolver ?? new MetadataResolver(
                new PropertyResolverProvider(
                    new \ArrayIterator(['default' => new DefaultPropertyResolver()]),
                ),
            ),
        );

        return new RoutableTemplateResolver($inner, $metadataProvider);
    }
}
