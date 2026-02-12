<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageLinkProviderTest extends TestCase
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
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private ObjectProphecy $referenceStore;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    private PageLinkProvider $pageLinkProvider;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $this->pageLinkProvider = new PageLinkProvider(
            $this->entityManager->reveal(),
            $this->webspaceManager->reveal(),
            $this->referenceStore->reveal(),
            $this->translator->reveal(),
        );
    }

    public function testGetConfigurationBuilder(): void
    {
        $linkConfigurationBuilder = $this->pageLinkProvider->getConfigurationBuilder();
        $linkConfiguration = $linkConfigurationBuilder->getLinkConfiguration();

        $this->assertSame(
            PageInterface::RESOURCE_KEY,
            self::getPrivateProperty($linkConfiguration, 'resourceKey'),
        );

        $this->assertSame(
            'column_list',
            self::getPrivateProperty($linkConfiguration, 'listAdapter'),
        );

        $this->assertSame([
            'title',
        ], self::getPrivateProperty($linkConfiguration, 'displayProperties'));
    }

    public function testPreloadEmptyHrefs(): void
    {
        $result = $this->pageLinkProvider->preload([], 'en');

        $this->assertSame([], $result);
    }

    public function testPreloadRegularPage(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'uuid-1',
                    'title' => 'Test Page',
                    'slug' => '/test-page',
                    'webspaceKey' => 'sulu_io',
                    'linkProvider' => null,
                    'linkData' => null,
                ],
            ],
            ['uuid-1'],
            'en',
            'live',
        );

        $this->webspaceManager->findUrlByResourceLocator('/test-page', null, 'en', 'sulu_io')
            ->willReturn('/en/test-page');

        $this->referenceStore->add('uuid-1', PageInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->pageLinkProvider->preload(['uuid-1'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('uuid-1', $result[0]->getId());
        $this->assertSame('Test Page', $result[0]->getTitle());
        $this->assertSame('/en/test-page', $result[0]->getUrl());
        $this->assertTrue($result[0]->isPublished());
    }

    public function testPreloadExternalLink(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'uuid-ext',
                    'title' => 'External Page',
                    'slug' => '/external-page',
                    'webspaceKey' => 'sulu_io',
                    'linkProvider' => 'external',
                    'linkData' => ['href' => 'https://example.com', 'query' => 'foo=bar', 'anchor' => 'section'],
                ],
            ],
            ['uuid-ext'],
            'en',
            'live',
        );

        $this->referenceStore->add('uuid-ext', PageInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->pageLinkProvider->preload(['uuid-ext'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('https://example.com?foo=bar#section', $result[0]->getUrl());
    }

    public function testPreloadInternalLink(): void
    {
        $mainQueryBuilder = $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'uuid-link',
                    'title' => 'Link Page',
                    'slug' => '/link-page',
                    'webspaceKey' => 'sulu_io',
                    'linkProvider' => 'page',
                    'linkData' => ['href' => 'uuid-target'],
                ],
            ],
            ['uuid-link'],
            'en',
            'live',
        );

        $targetQueryBuilder = $this->createQueryBuilderMock(
            [
                [
                    'uuid' => 'uuid-target',
                    'slug' => '/target-page',
                    'webspaceKey' => 'sulu_io',
                ],
            ],
            ['uuid-target'],
            'en',
            'live',
            'targetUuids',
        );

        $this->entityManager->createQueryBuilder()
            ->willReturn($mainQueryBuilder->reveal(), $targetQueryBuilder->reveal());

        $this->webspaceManager->findUrlByResourceLocator('/target-page', null, 'en', 'sulu_io')
            ->willReturn('/en/target-page');

        $this->referenceStore->add('uuid-link', PageInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->pageLinkProvider->preload(['uuid-link'], 'en', true)];

        $this->assertCount(1, $result);
        $this->assertSame('uuid-link', $result[0]->getId());
        $this->assertSame('Link Page', $result[0]->getTitle());
        $this->assertSame('/en/target-page', $result[0]->getUrl());
    }

    public function testPreloadMissingRoute(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'uuid-no-route',
                    'title' => 'No Route Page',
                    'slug' => null,
                    'webspaceKey' => 'sulu_io',
                    'linkProvider' => null,
                    'linkData' => null,
                ],
            ],
            ['uuid-no-route'],
            'en',
            'live',
        );

        $this->referenceStore->add('uuid-no-route', PageInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->pageLinkProvider->preload(['uuid-no-route'], 'en', true)];

        $this->assertCount(0, $result);
    }

    public function testPreloadDraftStage(): void
    {
        $this->mockQueryBuilder(
            [
                [
                    'uuid' => 'uuid-draft',
                    'title' => 'Draft Page',
                    'slug' => '/draft-page',
                    'webspaceKey' => 'sulu_io',
                    'linkProvider' => null,
                    'linkData' => null,
                ],
            ],
            ['uuid-draft'],
            'en',
            'draft',
        );

        $this->webspaceManager->findUrlByResourceLocator('/draft-page', null, 'en', 'sulu_io')
            ->willReturn('/en/draft-page');

        $this->referenceStore->add('uuid-draft', PageInterface::RESOURCE_KEY)->shouldBeCalled();

        $result = [...$this->pageLinkProvider->preload(['uuid-draft'], 'en', false)];

        $this->assertCount(1, $result);
        $this->assertSame('/en/draft-page', $result[0]->getUrl());
        $this->assertFalse($result[0]->isPublished());
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[] $expectedUuids
     *
     * @return ObjectProphecy<QueryBuilder>
     */
    private function mockQueryBuilder(
        array $rows,
        array $expectedUuids,
        string $expectedLocale,
        string $expectedStage,
    ): ObjectProphecy {
        $queryBuilder = $this->createQueryBuilderMock($rows, $expectedUuids, $expectedLocale, $expectedStage);
        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());

        return $queryBuilder;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[] $expectedUuids
     *
     * @return ObjectProphecy<QueryBuilder>
     */
    private function createQueryBuilderMock(
        array $rows,
        array $expectedUuids,
        string $expectedLocale,
        string $expectedStage,
        string $uuidParameterName = 'uuids',
    ): ObjectProphecy {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->from(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->join(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->leftJoin(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->andWhere(Argument::cetera())->willReturn($queryBuilder);

        $queryBuilder->setParameter($uuidParameterName, $expectedUuids)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameter('locale', $expectedLocale)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameter('stage', $expectedStage)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameter('version', 0)->willReturn($queryBuilder)->shouldBeCalled();

        $query = $this->prophesize(Query::class);
        $query->getArrayResult()->willReturn($rows);
        $queryBuilder->getQuery()->willReturn($query->reveal());

        return $queryBuilder;
    }
}
