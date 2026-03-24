<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleLinkProvider;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Application\Routing\Generator\WebspaceRouteGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleLinkProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private ObjectProphecy $entityManager;

    private RouteGeneratorInterface $routeGenerator;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private ObjectProphecy $referenceStore;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $webspaceRouteGenerator = new class() implements WebspaceRouteGeneratorInterface {
            public function generate(RequestContext $requestContext, string $slug, string $locale): string
            {
                return \sprintf('/%s%s', $locale, $slug);
            }
        };

        $this->routeGenerator = new class($webspaceRouteGenerator) implements RouteGeneratorInterface {
            public function __construct(private WebspaceRouteGeneratorInterface $webspaceRouteGenerator)
            {
            }

            public function generate(string $slug, ?string $locale = null, ?string $webspace = null, int $referenceType = 0): string
            {
                return $this->webspaceRouteGenerator->generate(new RequestContext(), $slug, $locale ?? 'en');
            }
        };
    }

    private function createProvider(
        ?string $requestWebspace = null,
        string $articleContentClass = ArticleDimensionContent::class,
    ): ArticleLinkProvider {
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        if (null !== $requestWebspace) {
            $webspace = new Webspace();
            $webspace->setKey($requestWebspace);
            $requestAnalyzer->getWebspace()->willReturn($webspace);
        } else {
            $requestAnalyzer->getWebspace()->willReturn(null);
        }

        return new ArticleLinkProvider(
            $this->entityManager->reveal(),
            $this->routeGenerator,
            $requestAnalyzer->reveal(),
            $this->referenceStore->reveal(),
            $this->translator->reveal(),
            $articleContentClass, // @phpstan-ignore argument.type
        );
    }

    public function testGetConfigurationBuilder(): void
    {
        $provider = $this->createProvider();
        $linkConfigurationBuilder = $provider->getConfigurationBuilder();
        $linkConfiguration = $linkConfigurationBuilder->getLinkConfiguration();

        $this->assertSame(
            ArticleInterface::RESOURCE_KEY,
            self::getPrivateProperty($linkConfiguration, 'resourceKey'),
        );

        $this->assertSame(
            'table',
            self::getPrivateProperty($linkConfiguration, 'listAdapter'),
        );

        $this->assertSame([
            'title',
        ], self::getPrivateProperty($linkConfiguration, 'displayProperties'));
    }

    public function testPreloadEmptyHrefs(): void
    {
        $provider = $this->createProvider();
        $result = $provider->preload([], 'en');

        $this->assertSame([], $result);
    }

    public function testPreloadWithMainWebspace(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-1',
                    'title' => 'Test Article',
                    'slug' => '/test-article',
                    'mainWebspace' => 'blog',
                    'additionalWebspace' => null,
                ],
            ],
            ['article-1'],
            'en',
            'live',
            'blog',
        );

        $this->referenceStore->add('article-1', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider('blog');
        $result = [...$provider->preload(['article-1'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('article-1', $result[0]->getId());
        $this->assertSame('Test Article', $result[0]->getTitle());
        $this->assertSame('/en/test-article', $result[0]->getUrl());
        $this->assertTrue($result[0]->isPublished());
    }

    public function testPreloadWithAdditionalWebspace(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-2',
                    'title' => 'Shared Article',
                    'slug' => '/shared-article',
                    'mainWebspace' => 'blog',
                    'additionalWebspace' => 'main_site',
                ],
            ],
            ['article-2'],
            'en',
            'live',
            'main_site',
        );

        $this->referenceStore->add('article-2', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider('main_site');
        $result = [...$provider->preload(['article-2'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/shared-article', $result[0]->getUrl());
    }

    public function testPreloadFallbackToMainWebspace(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-3',
                    'title' => 'Blog Article',
                    'slug' => '/blog-article',
                    'mainWebspace' => 'blog',
                    'additionalWebspace' => null,
                ],
            ],
            ['article-3'],
            'en',
            'live',
            'other_site',
        );

        $this->referenceStore->add('article-3', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider('other_site');
        $result = [...$provider->preload(['article-3'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/blog-article', $result[0]->getUrl());
    }

    public function testPreloadNoRequestWebspace(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-4',
                    'title' => 'CLI Article',
                    'slug' => '/cli-article',
                    'mainWebspace' => 'blog',
                ],
            ],
            ['article-4'],
            'en',
            'live',
            null,
        );

        $this->referenceStore->add('article-4', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider(null);
        $result = [...$provider->preload(['article-4'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/cli-article', $result[0]->getUrl());
    }

    public function testPreloadUsesConfiguredArticleContentClass(): void
    {
        /** @phpstan-ignore-next-line We intentionally pass a non-existent class to verify the parameter is forwarded */
        $customArticleContentClass = 'App\\Entity\\ArticleDimensionContent';

        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-7',
                    'title' => 'Extended Article',
                    'slug' => '/extended-article',
                    'mainWebspace' => 'blog',
                ],
            ],
            ['article-7'],
            'en',
            'live',
            null,
            $customArticleContentClass,
        );

        $this->referenceStore->add('article-7', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider(null, $customArticleContentClass);
        $result = [...$provider->preload(['article-7'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/extended-article', $result[0]->getUrl());
    }

    public function testPreloadDraftStage(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-5',
                    'title' => 'Draft Article',
                    'slug' => '/draft-article',
                    'mainWebspace' => 'blog',
                    'additionalWebspace' => null,
                ],
            ],
            ['article-5'],
            'en',
            'draft',
            'blog',
        );

        $this->referenceStore->add('article-5', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider('blog');
        $result = [...$provider->preload(['article-5'], 'en', false)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/draft-article', $result[0]->getUrl());
        $this->assertFalse($result[0]->isPublished());
    }

    public function testPreloadNullSlug(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-6',
                    'title' => 'No Route Article',
                    'slug' => null,
                    'mainWebspace' => 'blog',
                    'additionalWebspace' => null,
                ],
            ],
            ['article-6'],
            'en',
            'live',
            'blog',
        );

        $this->referenceStore->add('article-6', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $provider = $this->createProvider('blog');
        $result = [...$provider->preload(['article-6'], 'en', true)];

        $this->assertCount(0, $result);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[] $expectedUuids
     */
    private function mockQueryBuilder(
        array $rows,
        array $expectedUuids,
        string $expectedLocale,
        string $expectedStage,
        ?string $expectedRequestWebspace,
        string $expectedArticleContentClass = ArticleDimensionContent::class,
    ): void {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->from($expectedArticleContentClass, 'dimensionContent')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->join(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->leftJoin(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->andWhere(Argument::cetera())->willReturn($queryBuilder);

        $queryBuilder->setParameter('uuids', $expectedUuids)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameter('locale', $expectedLocale)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameter('stage', $expectedStage)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameter('version', 0)->willReturn($queryBuilder)->shouldBeCalled();

        if (null !== $expectedRequestWebspace) {
            $queryBuilder->addSelect('additionalWebspace.additionalWebspace')
                ->willReturn($queryBuilder)->shouldBeCalled();
            $queryBuilder->setParameter('requestWebspace', $expectedRequestWebspace)
                ->willReturn($queryBuilder)->shouldBeCalled();
        }

        $query = $this->prophesize(Query::class);
        $query->getArrayResult()->willReturn($rows);
        $queryBuilder->getQuery()->willReturn($query->reveal());

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
    }
}
