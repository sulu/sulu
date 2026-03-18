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
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Route\PageRouteDefaultsProvider;
use Sulu\Route\Domain\Model\Route;
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

    private CacheLifetimeResolver $cacheLifetimeResolver;

    private MetadataProviderRegistry $metadataProviderRegistry;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
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

    public function testGetDefaultsReturnNoneTemplate(): void
    {
        $provider = new PageRouteDefaultsProvider(
            $this->pageRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
        );

        $resolvedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedDimensionContent->getLocale()->willReturn('en');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($resolvedDimensionContent->reveal())
        ));

        $page = new Page('123-123-123');
        $page->setWebspaceKey('sulu-io');

        $this->pageRepository->findOneBy(
            Argument::cetera()
        )->willReturn($page);

        $this->contentAggregator->aggregate(
            $page,
            ['locale' => 'en', 'stage' => 'live', 'version' => 0]
        )->willReturn($resolvedDimensionContent->reveal());

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
