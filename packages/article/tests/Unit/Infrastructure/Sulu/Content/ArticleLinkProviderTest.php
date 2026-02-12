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
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleLinkProvider;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleLinkProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private ObjectProphecy $entityManager;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private ObjectProphecy $webspaceManager;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private ObjectProphecy $referenceStore;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    private ArticleLinkProvider $articleLinkProvider;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $this->articleLinkProvider = new ArticleLinkProvider(
            $this->entityManager->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->referenceStore->reveal(),
            $this->translator->reveal(),
        );
    }

    public function testGetConfigurationBuilder(): void
    {
        $linkConfigurationBuilder = $this->articleLinkProvider->getConfigurationBuilder();
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
        $result = $this->articleLinkProvider->preload([], 'en');

        $this->assertSame([], $result);
    }

    public function testPreloadWithMainWebspace(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

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

        $this->webspaceManager->findUrlByResourceLocator('/test-article', null, 'en', 'blog')
            ->willReturn('/en/test-article');

        $this->referenceStore->add('article-1', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-1'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('article-1', $result[0]->getId());
        $this->assertSame('Test Article', $result[0]->getTitle());
        $this->assertSame('/en/test-article', $result[0]->getUrl());
        $this->assertTrue($result[0]->isPublished());
    }

    public function testPreloadWithAdditionalWebspace(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('main_site');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

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

        $this->webspaceManager->findUrlByResourceLocator('/shared-article', null, 'en', 'main_site')
            ->willReturn('/en/shared-article');

        $this->referenceStore->add('article-2', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-2'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/shared-article', $result[0]->getUrl());
    }

    public function testPreloadFallbackToMainWebspace(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('other_site');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

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

        $this->webspaceManager->findUrlByResourceLocator('/blog-article', null, 'en', 'blog')
            ->willReturn('/en/blog-article');

        $this->referenceStore->add('article-3', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-3'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/blog-article', $result[0]->getUrl());
    }

    public function testPreloadNullWebspace(): void
    {
        $this->requestAnalyzer->getWebspace()->willReturn(null);

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

        $this->webspaceManager->findUrlByResourceLocator('/cli-article', null, 'en', 'blog')
            ->willReturn('/en/cli-article');

        $this->referenceStore->add('article-4', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-4'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/cli-article', $result[0]->getUrl());
    }

    public function testPreloadDraftStage(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

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

        $this->webspaceManager->findUrlByResourceLocator('/draft-article', null, 'en', 'blog')
            ->willReturn('/en/draft-article');

        $this->referenceStore->add('article-5', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-5'], 'en', false)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/draft-article', $result[0]->getUrl());
        $this->assertFalse($result[0]->isPublished());
    }

    public function testPreloadNullSlug(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

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

        $result = [...$this->articleLinkProvider->preload(['article-6'], 'en', true)];

        $this->assertCount(0, $result);
    }

    public function testPreloadNullMainWebspace(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-7',
                    'title' => 'Orphan Article',
                    'slug' => '/orphan-article',
                    'mainWebspace' => null,
                    'additionalWebspace' => null,
                ],
            ],
            ['article-7'],
            'en',
            'live',
            'blog',
        );

        $this->referenceStore->add('article-7', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-7'], 'en', true)];

        $this->assertCount(0, $result);
    }

    public function testPreloadUrlResolutionReturnsNull(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'article-8',
                    'title' => 'Unresolvable Article',
                    'slug' => '/unresolvable',
                    'mainWebspace' => 'blog',
                    'additionalWebspace' => null,
                ],
            ],
            ['article-8'],
            'en',
            'live',
            'blog',
        );

        $this->webspaceManager->findUrlByResourceLocator('/unresolvable', null, 'en', 'blog')
            ->willReturn(null);

        $this->referenceStore->add('article-8', ArticleInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->articleLinkProvider->preload(['article-8'], 'en', true)];

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
    ): void {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->from(Argument::cetera())->willReturn($queryBuilder);
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
