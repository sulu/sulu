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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Doctrine\Repository;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Functional\Traits\QueryCountTrait;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class SnippetRepositoryPreloadedCollectionsTest extends SuluTestCase
{
    use CreateSnippetTrait;
    use QueryCountTrait;

    private SnippetRepositoryInterface $snippetRepository;
    private ContentAggregatorInterface $contentAggregator;

    /**
     * @var string[]
     */
    private array $uuids = [];

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->snippetRepository = self::getContainer()->get('sulu_snippet.snippet_repository');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');

        $targetGroup = new TargetGroup();
        $targetGroup->setTitle('Target Group');
        $targetGroup->setPriority(1);
        self::getEntityManager()->persist($targetGroup);
        self::getEntityManager()->flush();

        foreach (['a', 'b', 'c'] as $key) {
            $this->uuids[] = self::createSnippet([
                'en' => ['live' => [
                    'template' => 'snippet',
                    'title' => 'Snippet ' . $key,
                    'excerptAudienceTargetGroups' => [$targetGroup->getId()],
                ]],
            ])->getUuid();
        }

        self::getEntityManager()->clear();
    }

    public function testFindByMergesTheCompleteCollections(): void
    {
        $snippets = \iterator_to_array($this->snippetRepository->findBy(
            ['uuids' => $this->uuids, 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true],
        ), false);

        $this->assertCount(3, $snippets);
        $this->assertSame(0, self::countQueries(fn () => $this->assertMergedCollections($snippets)));
    }

    public function testGetOneByMergesTheCompleteCollections(): void
    {
        $snippet = $this->snippetRepository->getOneBy(
            ['uuid' => $this->uuids[0], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true],
        );

        $this->assertSame(0, self::countQueries(fn () => $this->assertMergedCollections([$snippet])));
    }

    public function testFindOneByMergesTheCompleteCollections(): void
    {
        $snippet = $this->snippetRepository->findOneBy(
            ['uuid' => $this->uuids[0], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true],
        );
        $this->assertNotNull($snippet);

        $this->assertSame(0, self::countQueries(fn () => $this->assertMergedCollections([$snippet])));
    }

    /**
     * @param iterable<SnippetInterface> $snippets
     */
    private function assertMergedCollections(iterable $snippets): void
    {
        foreach ($snippets as $snippet) {
            $dimensionContent = $this->contentAggregator->aggregate($snippet, [
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);

            $this->assertCount(1, $dimensionContent->getExcerptAudienceTargetGroups());
        }
    }
}
