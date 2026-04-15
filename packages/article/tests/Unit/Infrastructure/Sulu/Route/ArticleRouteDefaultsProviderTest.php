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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Route;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Route\ArticleRouteDefaultsProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\DependencyInjection\Container;

class ArticleRouteDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ArticleRepositoryInterface> */
    private ObjectProphecy $articleRepository;
    /** @var ObjectProphecy<ContentAggregatorInterface> */
    private ObjectProphecy $contentAggregator;
    private MetadataProviderRegistry $metadataProviderRegistry;
    private CacheLifetimeResolver $cacheLifetimeResolver;
    /** @var ObjectProphecy<FormMetadataProvider> */
    private ObjectProphecy $formMetadataProvider;

    protected function setUp(): void
    {
        $this->articleRepository = $this->prophesize(ArticleRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $container = new Container();
        $container->set('form', $this->formMetadataProvider->reveal());
        $this->metadataProviderRegistry = new MetadataProviderRegistry($container);
    }

    protected function getArticleRouteDefaultsProviderInstance(): RouteDefaultsProviderInterface
    {
        return new ArticleRouteDefaultsProvider(
            $this->articleRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->metadataProviderRegistry,
            $this->cacheLifetimeResolver,
        );
    }

    public function testGetDefaults(): void
    {
        $provider = $this->getArticleRouteDefaultsProviderInstance();

        $locale = 'en';
        $slug = '/test-article';

        $article = new Article('123-123-123');
        $resolvedDimensionContent = new ArticleDimensionContent($article);
        $resolvedDimensionContent->setLocale($locale);
        $resolvedDimensionContent->setTemplateKey('default');

        $this->articleRepository->findOneBy(
            [
                'uuid' => '123-123-123',
            ],
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'dimensionAttributes' => [
                        'locale' => $locale,
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
        )->willReturn($article);

        $this->contentAggregator->aggregate($article, ['locale' => $locale, 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata('ArticleController::indexAction', 'article.html.twig', 'seconds', '3600');

        $route = new Route(
            Article::RESOURCE_KEY,
            '123-123-123',
            $locale,
            $slug,
        );

        $result = $provider->getDefaults($route);

        $this->assertArrayNotHasKey('_seo', $result);
        $this->assertSame($resolvedDimensionContent, $result['object']);
        $this->assertSame('article.html.twig', $result['view']);
        $this->assertSame('ArticleController::indexAction', $result['_controller']);
    }

    public function testGetDefaultsNotFound(): void
    {
        $provider = $this->getArticleRouteDefaultsProviderInstance();

        $this->articleRepository->findOneBy(Argument::cetera())->willReturn(null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $provider->getDefaults(new Route(Article::RESOURCE_KEY, '123-123-123', 'en', '/test-article'));
    }

    public function testGetDefaultsReturnsAggregatedContentForShadowLocaleRoute(): void
    {
        $provider = $this->getArticleRouteDefaultsProviderInstance();

        $routeLocale = 'de';
        $contentLocale = 'en';
        $slug = '/deutscher-artikel';

        $article = new Article('123-123-123');
        $resolvedDimensionContent = new ArticleDimensionContent($article);
        $resolvedDimensionContent->setLocale($contentLocale);
        $resolvedDimensionContent->setTemplateKey('default');

        $this->articleRepository->findOneBy(
            [
                'uuid' => '123-123-123',
            ],
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'dimensionAttributes' => [
                        'locale' => $routeLocale,
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
        )->willReturn($article);

        $this->contentAggregator->aggregate($article, ['locale' => $routeLocale, 'stage' => 'live', 'version' => 0])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'ArticleController::indexAction',
            'article.html.twig',
            'seconds',
            '3600',
            $contentLocale,
        );

        $route = new Route(
            Article::RESOURCE_KEY,
            '123-123-123',
            $routeLocale,
            $slug,
        );

        $result = $provider->getDefaults($route);

        $this->assertSame($resolvedDimensionContent, $result['object']);
        $this->assertSame('article.html.twig', $result['view']);
        $this->assertSame('ArticleController::indexAction', $result['_controller']);
    }

    private function prepareTemplateMetadata(
        string $controller,
        string $view,
        string $cacheLifeTimeType,
        string $cacheLifeTimeValue,
        ?string $locale = null,
    ): void {
        $typedMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $templateMetadata = new TemplateMetadata($controller, $view);
        $formMetadata->setTemplate($templateMetadata);

        $localeArgument = $locale ?? Argument::type('string');

        $this->formMetadataProvider->getMetadata(Article::TEMPLATE_TYPE, $localeArgument, [])
            ->willReturn($typedMetadata)
            ->shouldBeCalled();
    }
}
