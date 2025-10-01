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

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Infrastructure\Sulu\Route\ArticleRouteDefaultsProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;

class ArticleRouteDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;
    /** @var ObjectProphecy<ContentAggregatorInterface> */
    private ObjectProphecy $contentAggregator;
    private CacheLifetimeResolver $cacheLifetimeResolver;
    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;
    /** @var ObjectProphecy<WebspaceResolver> */
    private ObjectProphecy $webspaceResolver;
    /** @var ObjectProphecy<FormMetadataProvider> */
    private ObjectProphecy $formMetadataProvider;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->webspaceResolver = $this->prophesize(WebspaceResolver::class);
    }

    protected function getArticleRouteDefaultsProviderInstance(): RouteDefaultsProviderInterface
    {
        return new ArticleRouteDefaultsProvider(
            $this->entityManager->reveal(),
            $this->contentAggregator->reveal(),
            $this->formMetadataProvider->reveal(),
            $this->cacheLifetimeResolver,
            $this->webspaceManager->reveal(),
            $this->webspaceResolver->reveal(),
            'test'
        );
    }

    public function testGetDefaultsWithSeoData(): void
    {
        $provider = $this->getArticleRouteDefaultsProviderInstance();

        $locale = 'en';
        $slug = '/test-article';
        $mainWebspace = 'sulu-io';
        $canonicalUrl = 'https://sulu.io/test-article';

        // Create article dimension content that implements AdditionalWebspacesInterface
        $contentRichEntity = new Article();
        $resolvedDimensionContent = new ArticleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale($locale);
        $resolvedDimensionContent->setTemplateKey('default');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Article::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => $locale, 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata('ArticleController::indexAction', 'article.html.twig', 'seconds', '3600');

        $this->webspaceResolver->resolveMainWebspace($resolvedDimensionContent, $locale)
            ->willReturn($mainWebspace);

        $this->webspaceManager->findUrlByResourceLocator($slug, 'test', $locale, $mainWebspace)
            ->willReturn($canonicalUrl);

        $route = new Route(
            Article::RESOURCE_KEY,
            '123-123-123',
            $locale,
            $slug,
        );

        $result = $provider->getDefaults($route);

        $this->assertArrayHasKey('_seo', $result);
        $this->assertIsArray($result['_seo']);
        $this->assertSame($canonicalUrl, $result['_seo']['canonicalUrl']);
        $this->assertSame($resolvedDimensionContent, $result['object']);
        $this->assertSame('article.html.twig', $result['view']);
        $this->assertSame('ArticleController::indexAction', $result['_controller']);
    }

    public function testGetDefaultsWithoutSeoData(): void
    {
        $provider = $this->getArticleRouteDefaultsProviderInstance();

        $locale = 'en';
        $slug = '/test-article';

        // Create article dimension content that doesn't implement AdditionalWebspacesInterface
        $contentRichEntity = new Article();
        $resolvedDimensionContent = new ArticleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale($locale);
        $resolvedDimensionContent->setTemplateKey('default');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Article::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => $locale, 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata('ArticleController::indexAction', 'article.html.twig', 'seconds', '3600');

        // No webspace resolver calls should be made for non-webspace supporting content

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

    private function prepareTemplateMetadata(string $controller, string $view, string $cacheLifeTimeType, string $cacheLifeTimeValue): void
    {
        $typedMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $templateMetadata = new TemplateMetadata($controller, $view);
        $formMetadata->setTemplate($templateMetadata);

        $this->formMetadataProvider->getMetadata(Argument::cetera())
            ->willReturn($typedMetadata)
            ->shouldBeCalled();
    }
}
