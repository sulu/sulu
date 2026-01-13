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

namespace Sulu\Content\Tests\Unit\Infrastructure\Sulu\Traits;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Infrastructure\Sulu\Teaser\ContentTeaserProvider;
use Sulu\Content\Infrastructure\Sulu\Traits\ResolveContentDimensionUrlTrait;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;

/**
 * Test implementation of ResolveContentDimensionUrlTrait.
 */
class ResolveContentDimensionUrlTraitTestImpl
{
    use ResolveContentDimensionUrlTrait {
        getUrl as public;
    }

    private MetadataProviderRegistry $metadataProviderRegistry;
    private ?RouteGeneratorInterface $routeGenerator;

    public function __construct(MetadataProviderRegistry $metadataProviderRegistry, ?RouteGeneratorInterface $routeGenerator)
    {
        $this->metadataProviderRegistry = $metadataProviderRegistry;
        $this->routeGenerator = $routeGenerator;
    }

    protected function getMetadataProviderRegistry(): MetadataProviderRegistry
    {
        return $this->metadataProviderRegistry;
    }

    protected function getRouteGenerator(): ?RouteGeneratorInterface
    {
        return $this->routeGenerator;
    }
}

class ResolveContentDimensionUrlTraitTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private ObjectProphecy $routeGenerator;

    /**
     * @var ObjectProphecy<ContainerInterface>
     */
    private ObjectProphecy $metadataContainer;

    protected function setUp(): void
    {
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->metadataContainer = $this->prophesize(ContainerInterface::class);
    }

    private function createTraitInstance(?RouteGeneratorInterface $routeGenerator = null, bool $useNullRouteGenerator = false): ResolveContentDimensionUrlTraitTestImpl
    {
        $metadataProviderRegistry = new MetadataProviderRegistry($this->metadataContainer->reveal());

        if (!$useNullRouteGenerator && null === $routeGenerator) {
            $routeGenerator = $this->routeGenerator->reveal();
        }

        return new ResolveContentDimensionUrlTraitTestImpl($metadataProviderRegistry, $routeGenerator);
    }

    public function testGetUrlWithRoutableInterface(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');

        $route = new Route('examples', '1', 'en', '/my-example', 'sulu-io');
        $dimensionContent->setRoute($route);

        $this->routeGenerator->generate('/my-example', 'en', 'sulu-io')
            ->willReturn('/en/my-example')
            ->shouldBeCalledOnce();

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/en/my-example', $url);
    }

    public function testGetUrlWithRoutableInterfaceAndParentRoute(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');

        $parentRoute = new Route('pages', 'parent-1', 'en', '/products', 'sulu-io');
        $childRoute = new Route('examples', '1', 'en', '/laptop', 'sulu-io', $parentRoute);
        $dimensionContent->setRoute($childRoute);

        $this->routeGenerator->generate('/products/laptop', 'en', 'sulu-io')
            ->willReturn('/en/products/laptop')
            ->shouldBeCalledOnce();

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/en/products/laptop', $url);
    }

    public function testGetUrlWithRoutableInterfaceAndDeepParentChain(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');

        $grandparentRoute = new Route('pages', 'gp-1', 'en', '/shop', 'sulu-io');
        $parentRoute = new Route('pages', 'p-1', 'en', '/electronics', 'sulu-io', $grandparentRoute);
        $childRoute = new Route('examples', '1', 'en', '/laptop', 'sulu-io', $parentRoute);
        $dimensionContent->setRoute($childRoute);

        $this->routeGenerator->generate('/shop/electronics/laptop', 'en', 'sulu-io')
            ->willReturn('/en/shop/electronics/laptop')
            ->shouldBeCalledOnce();

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/en/shop/electronics/laptop', $url);
    }

    public function testGetUrlWithRoutableInterfaceNoRoute(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $this->setupMetadataForRouteField('url', 'route');

        $dimensionContent->setTemplateData(['url' => '/fallback-url']);

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/fallback-url', $url);
    }

    public function testGetUrlWithRoutableInterfaceNoRouteGenerator(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $route = new Route('examples', '1', 'en', '/my-example', 'sulu-io');
        $dimensionContent->setRoute($route);

        $this->setupMetadataForRouteField('url', 'route');
        $dimensionContent->setTemplateData(['url' => '/fallback-url']);

        $traitInstance = $this->createTraitInstance(useNullRouteGenerator: true);
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/fallback-url', $url);
    }

    public function testGetUrlWithPageTreeRoute(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $this->setupMetadataForRouteField('routePath', 'page_tree_route');

        $dimensionContent->setTemplateData([
            'routePath' => [
                'page' => ['path' => '/parent-page'],
                'suffix' => 'child-suffix',
            ],
        ]);

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/parent-page/child-suffix', $url);
    }

    public function testGetUrlWithPageTreeRouteTrimsSlashes(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $this->setupMetadataForRouteField('routePath', 'page_tree_route');

        $dimensionContent->setTemplateData([
            'routePath' => [
                'page' => ['path' => '/parent-page/'],
                'suffix' => '/child-suffix',
            ],
        ]);

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertSame('/parent-page/child-suffix', $url);
    }

    public function testGetUrlWithPageTreeRouteMissingPage(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $this->setupMetadataForRouteField('routePath', 'page_tree_route');

        $dimensionContent->setTemplateData([
            'routePath' => [
                'suffix' => 'child-suffix',
            ],
        ]);

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertNull($url);
    }

    public function testGetUrlWithPageTreeRouteMissingSuffix(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $this->setupMetadataForRouteField('routePath', 'page_tree_route');

        $dimensionContent->setTemplateData([
            'routePath' => [
                'page' => ['path' => '/parent-page'],
            ],
        ]);

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertNull($url);
    }

    public function testGetUrlWithPageTreeRouteNull(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $this->setupMetadataForRouteField('routePath', 'page_tree_route');

        $dimensionContent->setTemplateData([]);

        $traitInstance = $this->createTraitInstance();
        $url = $traitInstance->getUrl($dimensionContent, []);

        $this->assertNull($url);
    }

    public function testDeprecationWhenRouteGeneratorNull(): void
    {
        $this->expectUserDeprecationMessageMatches(
            '/Not passing a RouteGeneratorInterface to .+ is deprecated/'
        );

        $contentManager = $this->prophesize(ContentManagerInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $metadataProviderRegistry = new MetadataProviderRegistry($this->metadataContainer->reveal());

        new class(
            $contentManager->reveal(),
            $entityManager->reveal(),
            $contentMetadataInspector->reveal(),
            $metadataProviderRegistry,
            Example::class, /* @phpstan-ignore-line */
            null
        ) extends ContentTeaserProvider {
            public function getConfiguration(): TeaserConfiguration
            {
                throw new \RuntimeException('Not implemented');
            }
        };
    }

    private function setupMetadataForRouteField(string $fieldName, string $fieldType): void
    {
        $fieldMetadata = $this->prophesize(FieldMetadata::class);
        $fieldMetadata->getName()->willReturn($fieldName);
        $fieldMetadata->getType()->willReturn($fieldType);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()->willReturn([$fieldMetadata->reveal()]);

        $typedFormMetadata = $this->prophesize(TypedFormMetadata::class);
        $typedFormMetadata->getForms()->willReturn(['default' => $formMetadata->reveal()]);

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata(Example::TEMPLATE_TYPE, 'en', [])->willReturn($typedFormMetadata->reveal());

        $this->metadataContainer->has('form')->willReturn(true);
        $this->metadataContainer->get('form')->willReturn($metadataProvider->reveal());
    }
}
