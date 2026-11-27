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

namespace Sulu\Page\Tests\Functional\Infrastructure\Doctrine\Repository;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Functional\Traits\QueryCountTrait;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;

class PageRepositoryPreloadedCollectionsTest extends SuluTestCase
{
    use CreatePageTrait;
    use QueryCountTrait;

    private PageRepositoryInterface $pageRepository;
    private ContentAggregatorInterface $contentAggregator;

    /**
     * @var string[]
     */
    private array $uuids = [];

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->pageRepository = self::getContainer()->get('sulu_page.page_repository');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');

        $targetGroup = new TargetGroup();
        $targetGroup->setTitle('Target Group');
        $targetGroup->setPriority(1);
        self::getEntityManager()->persist($targetGroup);
        self::getEntityManager()->flush();

        foreach (['a', 'b', 'c'] as $key) {
            $this->uuids[] = self::createPage([
                'en' => ['live' => [
                    'template' => 'default',
                    'title' => 'Page ' . $key,
                    'url' => '/page-' . $key,
                    'navigationContexts' => ['main', 'footer'],
                    'excerptAudienceTargetGroups' => [$targetGroup->getId()],
                ]],
            ])->getUuid();
        }

        self::getEntityManager()->clear();
    }

    public function testFindByMergesTheCompleteCollections(): void
    {
        $pages = \iterator_to_array($this->pageRepository->findBy(
            ['uuids' => $this->uuids, 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [],
            [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
        ), false);

        $queries = self::countQueries(fn () => $this->assertMergedCollections($pages));

        $this->assertSame(0, $queries);
    }

    public function testFindByWithNavigationContextFilterMergesTheCompleteCollections(): void
    {
        $pages = \iterator_to_array($this->pageRepository->findBy(
            ['uuids' => $this->uuids, 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE, 'navigationContexts' => ['main']],
            [],
            [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
        ), false);

        $queries = self::countQueries(fn () => $this->assertMergedCollections($pages));

        $this->assertSame(0, $queries);
    }

    public function testFindOneByMergesTheCompleteCollections(): void
    {
        $page = $this->pageRepository->findOneBy(
            ['uuid' => $this->uuids[0], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
        );
        $this->assertNotNull($page);

        $queries = self::countQueries(fn () => $this->assertMergedCollections([$page]));

        $this->assertSame(0, $queries);
    }

    public function testGetOneByMergesTheCompleteCollections(): void
    {
        $page = $this->pageRepository->getOneBy(
            ['uuid' => $this->uuids[0], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
        );

        $queries = self::countQueries(fn () => $this->assertMergedCollections([$page]));

        $this->assertSame(0, $queries);
    }

    public function testFindByAsTreeMergesTheCompleteCollections(): void
    {
        $roots = \iterator_to_array($this->pageRepository->findByAsTree(
            ['webspaceKey' => 'sulu-io', 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [],
            [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
        ), false);

        $queries = self::countQueries(function() use ($roots): void {
            $pages = [];
            $this->collectPages($roots, $pages);
            $this->assertGreaterThanOrEqual(3, \count($pages));

            foreach ($pages as $page) {
                $dimensionContent = $this->aggregate($page);
                if (\in_array($page->getUuid(), $this->uuids, true)) {
                    $this->assertSame(['footer', 'main'], $this->sorted($dimensionContent->getNavigationContexts()));
                }
            }
        });

        $this->assertSame(0, $queries);
    }

    /**
     * @param PageInterface[] $pages
     */
    private function assertMergedCollections(array $pages): void
    {
        $this->assertNotEmpty($pages);
        foreach ($pages as $page) {
            $dimensionContent = $this->aggregate($page);

            $this->assertSame(['footer', 'main'], $this->sorted($dimensionContent->getNavigationContexts()));
            $this->assertCount(1, $dimensionContent->getExcerptAudienceTargetGroups());
        }
    }

    private function aggregate(PageInterface $page): PageDimensionContentInterface
    {
        /** @var PageDimensionContentInterface $dimensionContent */
        $dimensionContent = $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $dimensionContent;
    }

    /**
     * @param iterable<PageInterface> $pages
     * @param PageInterface[] $result
     */
    private function collectPages(iterable $pages, array &$result): void
    {
        foreach ($pages as $page) {
            $result[] = $page;
            $this->collectPages($page->getChildren(), $result);
        }
    }

    /**
     * @param string[] $values
     *
     * @return string[]
     */
    private function sorted(array $values): array
    {
        \sort($values);

        return $values;
    }
}
