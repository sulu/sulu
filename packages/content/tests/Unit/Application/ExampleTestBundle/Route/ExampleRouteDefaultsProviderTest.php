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

namespace Sulu\Content\Tests\Unit\Application\ExampleTestBundle\Route;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CacheLifetimeMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Content\Tests\Application\ExampleTestBundle\Route\ExampleRouteDefaultsProvider;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExampleRouteDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ExampleRepository> */
    private ObjectProphecy $exampleRepository;

    /** @var ObjectProphecy<ContentAggregatorInterface> */
    private ObjectProphecy $contentAggregator;

    /** @var ObjectProphecy<FormMetadataProvider> */
    private ObjectProphecy $formMetadataProvider;

    private CacheLifetimeResolver $cacheLifetimeResolver;

    private ExampleRouteDefaultsProvider $exampleRouteDefaultsProvider;

    protected function setUp(): void
    {
        $this->exampleRepository = $this->prophesize(ExampleRepository::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $container = new Container();
        $container->set('form', $this->formMetadataProvider->reveal());
        $metadataProviderRegistry = new MetadataProviderRegistry($container);

        $this->exampleRouteDefaultsProvider = new ExampleRouteDefaultsProvider(
            $this->exampleRepository->reveal(),
            $this->contentAggregator->reveal(),
            $metadataProviderRegistry,
            $this->cacheLifetimeResolver,
        );
    }

    public function testGetDefaults(): void
    {
        $example = new Example();
        $example->id = '123';
        $resolvedDimensionContent = new ExampleDimensionContent($example);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $this->exampleRepository->findOneBy(
            [
                'id' => '123',
            ],
            [
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    'dimensionAttributes' => [
                        'locale' => 'en',
                        'stage' => DimensionContentInterface::STAGE_LIVE,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    'selects' => [
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_TAGS => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES_TRANSLATION => true,
                    ],
                ],
            ]
        )->willReturn($example);

        $this->contentAggregator->aggregate($example, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'App\Controller\TestController:testAction',
            'default',
            CacheLifetimeResolverInterface::TYPE_SECONDS,
            '3600',
        );

        $route = new Route(
            Example::RESOURCE_KEY,
            '123',
            'en',
            '/example',
        );

        $result = $this->exampleRouteDefaultsProvider->getDefaults($route);

        $this->assertSame([
            'object' => $resolvedDimensionContent,
            'view' => 'default',
            '_controller' => 'App\Controller\TestController:testAction',
            '_cacheLifetime' => 3600,
        ], $result);
    }

    public function testGetDefaultsWithStringResourceId(): void
    {
        $example = new Example();
        $example->id = 'example-id';
        $resolvedDimensionContent = new ExampleDimensionContent($example);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $this->exampleRepository->findOneBy(
            [
                'id' => 'example-id',
            ],
            [
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    'dimensionAttributes' => [
                        'locale' => 'en',
                        'stage' => DimensionContentInterface::STAGE_LIVE,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    'selects' => [
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_TAGS => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES_TRANSLATION => true,
                    ],
                ],
            ]
        )->willReturn($example);

        $this->contentAggregator->aggregate($example, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'App\Controller\TestController:testAction',
            'default',
            CacheLifetimeResolverInterface::TYPE_SECONDS,
            '3600',
        );

        $result = $this->exampleRouteDefaultsProvider->getDefaults(
            new Route(Example::RESOURCE_KEY, 'example-id', 'en', '/example')
        );

        $this->assertSame($resolvedDimensionContent, $result['object']);
    }

    public function testGetDefaultsUsesAudienceTargetingSelectWhenEnabled(): void
    {
        $container = new Container();
        $container->set('form', $this->formMetadataProvider->reveal());
        $metadataProviderRegistry = new MetadataProviderRegistry($container);
        $provider = new ExampleRouteDefaultsProvider(
            $this->exampleRepository->reveal(),
            $this->contentAggregator->reveal(),
            $metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            true,
        );

        $example = new Example();
        $example->id = '123';
        $resolvedDimensionContent = new ExampleDimensionContent($example);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $this->exampleRepository->findOneBy(
            [
                'id' => '123',
            ],
            [
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    'dimensionAttributes' => [
                        'locale' => 'en',
                        'stage' => DimensionContentInterface::STAGE_LIVE,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    'selects' => [
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_TAGS => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES_TRANSLATION => true,
                        DimensionContentQueryEnhancer::SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS => true,
                    ],
                ],
            ]
        )->willReturn($example);

        $this->contentAggregator->aggregate($example, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'App\Controller\TestController:testAction',
            'default',
            CacheLifetimeResolverInterface::TYPE_SECONDS,
            '3600',
        );

        $provider->getDefaults(
            new Route(Example::RESOURCE_KEY, '123', 'en', '/example')
        );
    }

    public function testGetDefaultsNotPublishedInLocale(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $example = new Example();
        $example->id = '123';

        $this->exampleRepository->findOneBy(
            Argument::cetera()
        )->willReturn($example);

        $this->contentAggregator->aggregate(
            $example,
            ['locale' => 'en', 'stage' => 'live', 'version' => 0]
        )->willThrow(new ContentNotFoundException($example, ['locale' => 'en']));

        $route = new Route(
            Example::RESOURCE_KEY,
            '123',
            'en',
            '/example',
        );

        $this->exampleRouteDefaultsProvider->getDefaults($route);
    }

    private function prepareTemplateMetadata(string $controller, string $view, string $cacheLifeTimeType, string $cacheLifeTimeValue): void
    {
        $typedMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $templateMetadata = new TemplateMetadata($controller, $view);
        $formMetadata->setTemplate($templateMetadata);

        $cacheLifetimeMetadata = new CacheLifetimeMetadata($cacheLifeTimeType, $cacheLifeTimeValue);
        $templateMetadata->setCacheLifetime($cacheLifetimeMetadata);

        $this->formMetadataProvider->getMetadata(Argument::cetera())
            ->willReturn($typedMetadata)
            ->shouldBeCalled();
    }
}
