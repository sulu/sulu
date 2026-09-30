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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\Messenger\Envelope;

class PageRepositoryLoadedDimensionContentsTest extends SuluTestCase
{
    use CreatePageTrait;

    private EntityManagerInterface $entityManager;
    private PageRepositoryInterface $pageRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();
        $this->entityManager = self::getEntityManager();
        $this->pageRepository = self::getContainer()->get('sulu_page.page_repository');

        self::createPage([
            'en' => ['live' => ['template' => 'default', 'title' => 'Homepage', 'url' => '/']],
        ]);
    }

    public function testModifyOtherLocaleAfterReadingOneLocale(): void
    {
        $uuid = $this->createTwoLocalePage()->getUuid();
        $this->entityManager->clear();

        $this->loadPage($uuid, 'en');

        self::getContainer()->get('sulu_message_bus')->dispatch(new Envelope(
            new ModifyPageMessage(['uuid' => $uuid], [
                'locale' => 'de',
                'template' => 'default',
                'title' => 'Seite geändert',
                'url' => '/seite',
            ]),
            [new EnableFlushStamp()],
        ));

        $this->assertSame(1, $this->countCurrentDimensionContents($uuid, 'de', DimensionContentInterface::STAGE_DRAFT));
    }

    public function testHeldPageFindsLocaleLoadedLater(): void
    {
        $uuid = $this->createTwoLocalePage()->getUuid();
        $this->entityManager->clear();

        $page = $this->loadPage($uuid, 'en');
        $this->loadPage($uuid, 'de');

        $this->assertSame('Page', $this->findDraft($page, 'en')?->getTitle());
        $this->assertSame('Seite', $this->findDraft($page, 'de')?->getTitle());
    }

    public function testListFindsLocaleAfterWritingOtherLocale(): void
    {
        $uuid = $this->createTwoLocalePage()->getUuid();
        $this->entityManager->clear();

        self::getContainer()->get('sulu_message_bus')->dispatch(new Envelope(
            new ModifyPageMessage(['uuid' => $uuid], [
                'locale' => 'de',
                'template' => 'default',
                'title' => 'Seite geändert',
                'url' => '/seite',
            ]),
            [new EnableFlushStamp()],
        ));

        $pages = \iterator_to_array($this->pageRepository->findBy(
            ['uuids' => [$uuid], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [],
            [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
        ), false);

        $this->assertCount(1, $pages);
        $this->assertSame('Page', $this->findLive($pages[0], 'en')?->getTitle());
    }

    public function testCompletingDimensionContentsWritesNothingOnFlush(): void
    {
        $uuid = $this->createTwoLocalePage()->getUuid();
        $this->entityManager->clear();

        $this->loadPage($uuid, 'en');
        $this->loadPage($uuid, 'de');

        $this->assertSame(0, $this->countQueries(fn () => $this->entityManager->flush()));
    }

    public function testUnlocalizedLoadFindsStageLoadedLater(): void
    {
        $uuid = $this->createTwoLocalePage()->getUuid();
        $this->entityManager->clear();

        $page = $this->loadPage($uuid, 'en', [DimensionContentInterface::STAGE_DRAFT]);
        $this->loadPage($uuid, null, [DimensionContentInterface::STAGE_LIVE]);

        $this->assertNotNull($this->findDimensionContent($page, null, DimensionContentInterface::STAGE_LIVE));
    }

    public function testEntityWithoutRequestedRowIsCompletedOnce(): void
    {
        $uuid = self::createPage([
            'en' => ['draft' => ['template' => 'default', 'title' => 'Draft Page', 'url' => '/draft-page']],
        ])->getUuid();
        $this->entityManager->clear();

        $this->loadPage($uuid, 'en');
        $this->loadPage($uuid, 'en');

        $this->assertSame(1, $this->countQueries(fn () => $this->loadPage($uuid, 'en')));
    }

    public function testUnflushedDimensionContentSurvivesAnotherLoad(): void
    {
        $uuid = $this->createTwoLocalePage()->getUuid();
        $this->entityManager->clear();

        $page = $this->loadPage($uuid, 'en');
        $dimensionContent = $page->createDimensionContent();
        $dimensionContent->setLocale('fr');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $page->addDimensionContent($dimensionContent);

        $this->loadPage($uuid, 'en');

        $this->assertSame($dimensionContent, $this->findDraft($page, 'fr'));
    }

    public function testFindByLoadsDimensionContentsInOneQuery(): void
    {
        $uuids = [
            $this->createTwoLocalePage('/one')->getUuid(),
            $this->createTwoLocalePage('/two')->getUuid(),
            $this->createTwoLocalePage('/three')->getUuid(),
        ];
        $this->entityManager->clear();

        $pages = [];
        $queries = $this->countQueries(function() use ($uuids, &$pages): void {
            $pages = \iterator_to_array($this->pageRepository->findBy(
                ['uuids' => $uuids, 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
                [],
                [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
            ), false);

            foreach ($pages as $page) {
                $this->findLive($page, 'en');
            }
        });

        $this->assertCount(3, $pages);
        $this->assertSame(1, $queries);
        foreach ($pages as $page) {
            $this->assertNotNull($this->findLive($page, 'en'));
            $this->assertNull($this->findLive($page, 'de'));
        }
    }

    public function testFindByAsTreeLoadsDimensionContentsInOneQuery(): void
    {
        $parent = $this->createTwoLocalePage('/parent');
        self::createPage([
            'en' => ['live' => ['template' => 'default', 'title' => 'Child', 'url' => '/parent/child', 'parentId' => $parent->getUuid()]],
        ]);
        $this->entityManager->clear();

        $roots = [];
        $queries = $this->countQueries(function() use (&$roots): void {
            $roots = \iterator_to_array($this->pageRepository->findByAsTree(
                ['webspaceKey' => 'sulu-io', 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
                [],
                [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true],
            ), false);

            $this->walkTree($roots, fn (PageInterface $page) => $this->findLive($page, 'en'));
        });

        $this->assertSame(1, $queries);
        $this->assertCount(2, $roots);

        $titles = [];
        $this->walkTree($roots, function(PageInterface $page) use (&$titles): void {
            $titles[] = $this->findLive($page, 'en')?->getTitle();
        });
        $this->assertEqualsCanonicalizing(['Homepage', 'Page', 'Child'], $titles);
    }

    private function createTwoLocalePage(string $url = '/page'): PageInterface
    {
        return self::createPage([
            'en' => ['live' => ['template' => 'default', 'title' => 'Page', 'url' => $url]],
            'de' => ['live' => ['template' => 'default', 'title' => 'Seite', 'url' => $url]],
        ]);
    }

    /**
     * @param string[] $stages
     */
    private function loadPage(
        string $uuid,
        ?string $locale,
        array $stages = [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
    ): PageInterface {
        return $this->pageRepository->getOneBy(
            ['uuid' => $uuid],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => ['locale' => $locale, 'stage' => $stages],
                ],
            ],
        );
    }

    private function findDraft(PageInterface $page, string $locale): ?PageDimensionContentInterface
    {
        return $this->findDimensionContent($page, $locale, DimensionContentInterface::STAGE_DRAFT);
    }

    private function findLive(PageInterface $page, string $locale): ?PageDimensionContentInterface
    {
        return $this->findDimensionContent($page, $locale, DimensionContentInterface::STAGE_LIVE);
    }

    private function findDimensionContent(PageInterface $page, ?string $locale, string $stage): ?PageDimensionContentInterface
    {
        $dimensionAttributes = ['locale' => $locale, 'stage' => $stage];
        $collection = new DimensionContentCollection($page->getDimensionContents(), $dimensionAttributes, PageDimensionContent::class);

        /** @var PageDimensionContentInterface|null */
        return $collection->getDimensionContent($dimensionAttributes);
    }

    private function countCurrentDimensionContents(string $uuid, string $locale, string $stage): int
    {
        return (int) $this->createCountQueryBuilder($uuid)
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('locale', $locale)
            ->setParameter('stage', $stage)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createCountQueryBuilder(string $uuid): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(dimensionContent.id)')
            ->from(PageDimensionContent::class, 'dimensionContent')
            ->where('IDENTITY(dimensionContent.page) = :uuid')
            ->setParameter('uuid', $uuid);
    }

    /**
     * @param iterable<PageInterface> $pages
     */
    private function walkTree(iterable $pages, callable $visit): void
    {
        foreach ($pages as $page) {
            $visit($page);
            $this->walkTree($page->getChildren(), $visit);
        }
    }

    private function countQueries(callable $run): int
    {
        if (!self::getContainer()->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('Counting queries needs the debug data holder of DoctrineBundle 2.7 or later.');
        }

        /** @var DebugDataHolder $debugDataHolder */
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        $debugDataHolder->reset();

        $run();

        /** @var array<string, list<mixed>> $queries */
        $queries = $debugDataHolder->getData();

        return \count($queries['default'] ?? []);
    }
}
