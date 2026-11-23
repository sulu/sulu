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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Functional\Traits\CreateExampleTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Functional\Traits\QueryCountTrait;

class DimensionContentQueryEnhancerCollectionsTest extends SuluTestCase
{
    use CreateExampleTrait;
    use CreateTagTrait;
    use QueryCountTrait;

    private DimensionContentQueryEnhancer $enhancer;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->enhancer = self::getContainer()->get('sulu_content.dimension_content_query_enhancer');

        $tags = [self::createTag(['name' => 'one']), self::createTag(['name' => 'two'])];
        for ($i = 0; $i < 5; ++$i) {
            self::createExampleContent(self::createExample(), ['templateData' => ['title' => 'Example ' . $i], 'excerptTags' => $tags]);
        }

        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
    }

    public function testInitializesCollectionsWithOneQuery(): void
    {
        [$queryBuilder, $examples] = $this->findExamples(true);

        $queries = self::countQueries(fn () => $this->enhancer->initializeCollections($queryBuilder, $examples, ['excerptTags']));

        $this->assertSame(1, $queries);
        $this->assertSame(0, self::countQueries(fn () => $this->assertTagsLoaded($examples)));
    }

    public function testSkipsInitializedCollections(): void
    {
        [$queryBuilder, $examples] = $this->findExamples(true);
        $this->enhancer->initializeCollections($queryBuilder, $examples, ['excerptTags']);

        $this->assertSame(0, self::countQueries(fn () => $this->enhancer->initializeCollections($queryBuilder, $examples, ['excerptTags'])));
    }

    public function testSkipsQueriesWithoutSelectedDimensionContents(): void
    {
        [$queryBuilder, $examples] = $this->findExamples(false);

        $this->assertSame(0, self::countQueries(fn () => $this->enhancer->initializeCollections($queryBuilder, $examples, ['excerptTags'])));
    }

    public function testInitializesNothingWithoutAssociations(): void
    {
        [$queryBuilder, $examples] = $this->findExamples(true);

        $this->assertSame(0, self::countQueries(fn () => $this->enhancer->initializeCollections($queryBuilder, $examples)));
    }

    public function testIgnoresUnknownAssociations(): void
    {
        [$queryBuilder, $examples] = $this->findExamples(true);

        $this->assertSame(0, self::countQueries(fn () => $this->enhancer->initializeCollections($queryBuilder, $examples, ['doesNotExist'])));
    }

    public function testTargetGroupSelectDoesNotJoinTheCollection(): void
    {
        $queryBuilder = self::getEntityManager()->createQueryBuilder()
            ->select('example')
            ->from(Example::class, 'example')
            ->leftJoin('example.dimensionContents', 'dimensionContent');

        $this->enhancer->addSelects(
            $queryBuilder,
            ExampleDimensionContent::class,
            ['locale' => 'en', 'stage' => 'draft'],
            [DimensionContentQueryEnhancer::SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS => true],
        );

        $this->assertStringNotContainsString('excerptAudienceTargetGroups', $queryBuilder->getDQL());
    }

    public function testLeavesNothingToFlush(): void
    {
        [$queryBuilder, $examples] = $this->findExamples(true);
        $this->enhancer->initializeCollections($queryBuilder, $examples, ['excerptTags']);

        $this->assertSame(0, self::countQueries(fn () => self::getEntityManager()->flush()));
    }

    /**
     * @return array{QueryBuilder, Example[]}
     */
    private function findExamples(bool $withContent): array
    {
        $queryBuilder = self::getEntityManager()->createQueryBuilder()
            ->select('example')
            ->from(Example::class, 'example');

        if ($withContent) {
            $queryBuilder->leftJoin('example.dimensionContents', 'dimensionContent');
            $this->enhancer->addSelects($queryBuilder, ExampleDimensionContent::class, ['locale' => 'en', 'stage' => 'draft']);
        }

        /** @var Example[] $examples */
        $examples = $queryBuilder->getQuery()->getResult();

        return [$queryBuilder, $examples];
    }

    /**
     * @param Example[] $examples
     */
    private function assertTagsLoaded(array $examples): void
    {
        $this->assertCount(5, $examples);
        foreach ($examples as $example) {
            foreach ($example->getDimensionContents() as $dimensionContent) {
                $this->assertCount(null === $dimensionContent->getLocale() ? 0 : 2, $dimensionContent->getExcerptTags());
            }
        }
    }
}
