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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Route;

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
use Sulu\Bundle\MarkupBundle\Markup\Link\ExternalLinkProvider;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Page\Infrastructure\Sulu\Route\PageRouteDefaultsProvider;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageRouteDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<PageRepositoryInterface> */
    private ObjectProphecy $pageRepository;

    /** @var ObjectProphecy<ContentAggregatorInterface> */
    private ObjectProphecy $contentAggregator;

    /** @var ObjectProphecy<FormMetadataProvider> */
    private ObjectProphecy $formMetadataProvider;

    /** @var ObjectProphecy<RouteRepositoryInterface> */
    private ObjectProphecy $routeRepository;

    /** @var ObjectProphecy<RouteGeneratorInterface> */
    private ObjectProphecy $routeGenerator;

    private CacheLifetimeResolver $cacheLifetimeResolver;

    private MetadataProviderRegistry $metadataProviderRegistry;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $container = new Container();
        $container->set('form', $this->formMetadataProvider->reveal());
        $this->metadataProviderRegistry = new MetadataProviderRegistry($container);
    }

    public function testGetDefaults(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $this->pageRepository->findOneBy(
            [
                'uuid' => '123-123-123',
            ],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
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
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'App\Controller\TestController:testAction',
            'default',
            CacheLifetimeResolverInterface::TYPE_SECONDS,
            '3600',
        );

        $route = new Route(
            Page::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $result = $provider->getDefaults($route);

        $this->assertSame([
            'object' => $resolvedDimensionContent,
            'view' => 'default',
            '_controller' => 'App\Controller\TestController:testAction',
            '_cacheLifetime' => 3600,
        ], $result);
    }

    public function testGetDefaultsUsesAudienceTargetingSelectWhenEnabled(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
            true,
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $this->pageRepository->findOneBy(
            [
                'uuid' => '123-123-123',
            ],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
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
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'App\Controller\TestController:testAction',
            'default',
            CacheLifetimeResolverInterface::TYPE_SECONDS,
            '3600',
        );

        $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example')
        );
    }

    public function testGetDefaultsNotPublishedInLocale(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $this->expectException(NotFoundHttpException::class);

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate(
            $page,
            ['locale' => 'en', 'stage' => 'live', 'version' => 0]
        )->willThrow(new ContentNotFoundException($page, ['locale' => 'en']));

        $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example')
        );
    }

    public function testGetDefaultsReturnsRedirectForInternalPageLink(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');
        $resolvedDimensionContent->setLinkData([
            'provider' => PageLinkProvider::ALIAS,
            'href' => '456-456-456',
        ]);

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $targetRoute = new Route(Page::RESOURCE_KEY, '456-456-456', 'en', '/target-page', 'sulu-io');
        $this->routeRepository->findOneBy([
            'resourceKey' => Page::RESOURCE_KEY,
            'resourceId' => '456-456-456',
            'locale' => 'en',
        ])->willReturn($targetRoute);

        $this->routeGenerator->generate('/target-page', 'en', 'sulu-io')->willReturn('/en/target-page');

        $result = $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example', 'sulu-io')
        );

        $this->assertSame([
            '_controller' => RedirectController::class,
            'path' => '/en/target-page',
            'permanent' => true,
            '_sulu_route_target' => $targetRoute,
        ], $result);
    }

    public function testGetDefaultsReturnsRedirectForExternalLink(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');
        $resolvedDimensionContent->setLinkData([
            'provider' => ExternalLinkProvider::ALIAS,
            'href' => 'https://example.com/target',
        ]);

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $result = $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example', 'sulu-io')
        );

        $this->assertSame([
            '_controller' => RedirectController::class,
            'path' => 'https://example.com/target',
            'permanent' => true,
        ], $result);
    }

    public function testGetDefaultsThrowsNotFoundForMissingTargetRoute(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');
        $resolvedDimensionContent->setLinkData([
            'provider' => PageLinkProvider::ALIAS,
            'href' => '999-999-999',
        ]);

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->routeRepository->findOneBy([
            'resourceKey' => Page::RESOURCE_KEY,
            'resourceId' => '999-999-999',
            'locale' => 'en',
        ])->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No linked target route found for page "999-999-999" and locale "en"');

        $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example', 'sulu-io')
        );
    }

    public function testGetDefaultsAppendsQueryAndAnchorForInternalPageLink(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');
        $resolvedDimensionContent->setLinkData([
            'provider' => PageLinkProvider::ALIAS,
            'href' => '456-456-456',
            'query' => 'foo=bar',
            'anchor' => 'section',
        ]);

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $targetRoute = new Route(Page::RESOURCE_KEY, '456-456-456', 'en', '/target-page', 'sulu-io');
        $this->routeRepository->findOneBy([
            'resourceKey' => Page::RESOURCE_KEY,
            'resourceId' => '456-456-456',
            'locale' => 'en',
        ])->willReturn($targetRoute);

        $this->routeGenerator->generate('/target-page', 'en', 'sulu-io')->willReturn('/en/target-page');

        $result = $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example', 'sulu-io')
        );

        $this->assertSame('/en/target-page?foo=bar#section', $result['path']);
    }

    public function testGetDefaultsAppendsQueryAndAnchorForExternalLink(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
            $this->routeRepository->reveal(),
            $this->routeGenerator->reveal(),
        );

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');
        $resolvedDimensionContent = new PageDimensionContent($page);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');
        $resolvedDimensionContent->setLinkData([
            'provider' => ExternalLinkProvider::ALIAS,
            'href' => 'https://example.com/target',
            'query' => 'utm_source=sulu',
            'anchor' => 'top',
        ]);

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $result = $provider->getDefaults(
            new Route(Page::RESOURCE_KEY, '123-123-123', 'en', '/example', 'sulu-io')
        );

        $this->assertSame('https://example.com/target?utm_source=sulu#top', $result['path']);
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
