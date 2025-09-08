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

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Infrastructure\Sulu\Content\SmartContent;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;

/**
 * Tests for the MediaSmartContentProvider.
 *
 * @phpstan-type MediaData array{
 *     title?: string,
 *     description?: string,
 *     locale?: string,
 *     tags?: string[],
 *     categories?: int[],
 * }
 *
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class MediaSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;
    use CreateMediaTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, MediaInterface>
     */
    private static array $media = [];

    /**
     * @var array<string, CategoryInterface>
     */
    private static array $categories = [];

    /**
     * @var array<string, string>
     */
    private static array $tags = [];

    /**
     * @var array<string, CollectionInterface>
     */
    private static array $collections = [];

    /**
     * @var array<string, MediaType>
     */
    private static array $mediaTypes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->smartContentProvider = $this->getContainer()->get('sulu_media.media_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        self::purgeDatabase();
        self::bootKernel();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        self::$categories['tech'] = self::createCategory(['en' => ['title' => 'Technology']]);
        self::$categories['sports'] = self::createCategory(['en' => ['title' => 'Sports']]);
        self::$categories['health'] = self::createCategory(['en' => ['title' => 'Health']]);
        self::$categories['business'] = self::createCategory(['en' => ['title' => 'Business']]);
        self::$categories['entertainment'] = self::createCategory(['en' => ['title' => 'Entertainment']]);

        self::$tags = [
            'mobile' => 'mobile',
            'web' => 'web',
            'cloud' => 'cloud',
            'football' => 'football',
            'tennis' => 'tennis',
            'fitness' => 'fitness',
            'diet' => 'diet',
            'startup' => 'startup',
            'finance' => 'finance',
            'movies' => 'movies',
            'music' => 'music',
        ];

        self::$mediaTypes['image'] = self::createMediaType(['name' => 'image', 'description' => 'Image files']);
        self::$mediaTypes['video'] = self::createMediaType(['name' => 'video', 'description' => 'Video files']);
        self::$mediaTypes['document'] = self::createMediaType(['name' => 'document', 'description' => 'Document files']);

        self::$collections['main'] = self::createCollection(['title' => 'Main Collection', 'locale' => 'en', 'key' => 'main']);
        self::$collections['tech'] = self::createCollection(['title' => 'Tech Collection', 'locale' => 'en', 'key' => 'tech']);
        self::$collections['sports'] = self::createCollection(['title' => 'Sports Collection', 'locale' => 'en', 'key' => 'sports']);

        $entityManager->flush();

        // Technology media
        self::$media['tech1'] = self::createAndEnhanceMedia(
            self::$collections['tech'],
            self::$mediaTypes['image'],
            [
                'title' => 'Latest in Tech',
                'description' => 'Technology image',
                'locale' => 'en',
            ],
            [self::$categories['tech']->getId()],
            [self::$tags['mobile'], self::$tags['web']],
            new \DateTime('2023-01-01 10:00:00'),
        );

        self::$media['tech2'] = self::createAndEnhanceMedia(
            self::$collections['tech'],
            self::$mediaTypes['video'],
            [
                'title' => 'Cloud Computing',
                'description' => 'Cloud video',
                'locale' => 'en',
            ],
            [self::$categories['tech']->getId(), self::$categories['business']->getId()],
            [self::$tags['cloud']],
            new \DateTime('2023-01-01 11:00:00'),
        );

        // Sports media
        self::$media['sports1'] = self::createAndEnhanceMedia(
            self::$collections['sports'],
            self::$mediaTypes['image'],
            [
                'title' => 'Football Season',
                'description' => 'Football image',
                'locale' => 'en',
            ],
            [self::$categories['sports']->getId()],
            [self::$tags['football']],
            new \DateTime('2023-01-01 12:00:00'),
        );

        self::$media['sports2'] = self::createAndEnhanceMedia(
            self::$collections['sports'],
            self::$mediaTypes['video'],
            [
                'title' => 'Tennis Championship',
                'description' => 'Tennis video',
                'locale' => 'en',
            ],
            [self::$categories['sports']->getId()],
            [self::$tags['tennis']],
            new \DateTime('2023-01-01 13:00:00'),
        );

        // Health media
        self::$media['health1'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['document'],
            [
                'title' => 'Fitness Tips',
                'description' => 'Fitness document',
                'locale' => 'en',
            ],
            [self::$categories['health']->getId()],
            [self::$tags['fitness']],
            new \DateTime('2023-01-01 14:00:00'),
        );

        self::$media['health2'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['image'],
            [
                'title' => 'Healthy Diet',
                'description' => 'Diet image',
                'locale' => 'en',
            ],
            [self::$categories['health']->getId()],
            [self::$tags['diet'], self::$tags['fitness']],
            new \DateTime('2023-01-01 15:00:00'),
        );

        // Business media
        self::$media['business1'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['document'],
            [
                'title' => 'Startup News',
                'description' => 'Startup document',
                'locale' => 'en',
            ],
            [self::$categories['business']->getId()],
            [self::$tags['startup']],
            new \DateTime('2023-01-01 16:00:00'),
        );

        self::$media['business2'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['image'],
            [
                'title' => 'Finance Report',
                'description' => 'Finance image',
                'locale' => 'en',
            ],
            [self::$categories['business']->getId()],
            [self::$tags['finance']],
            new \DateTime('2023-01-01 17:00:00'),
        );

        // Entertainment media
        self::$media['entertainment1'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['video'],
            [
                'title' => 'Movie Reviews',
                'description' => 'Movie video',
                'locale' => 'en',
            ],
            [self::$categories['entertainment']->getId()],
            [self::$tags['movies']],
            new \DateTime('2023-01-01 18:00:00'),
        );

        self::$media['entertainment2'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['video'],
            [
                'title' => 'Music Festival',
                'description' => 'Music video',
                'locale' => 'en',
            ],
            [self::$categories['entertainment']->getId()],
            [self::$tags['music']],
            new \DateTime('2023-01-01 19:00:00'),
        );

        // Cross-category media
        self::$media['tech_health'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['image'],
            [
                'title' => 'Tech in Healthcare',
                'description' => 'Tech health image',
                'locale' => 'en',
            ],
            [self::$categories['tech']->getId(), self::$categories['health']->getId()],
            [self::$tags['mobile'], self::$tags['fitness']],
            new \DateTime('2023-01-01 20:00:00'),
        );

        self::$media['sports_health'] = self::createAndEnhanceMedia(
            self::$collections['sports'],
            self::$mediaTypes['document'],
            [
                'title' => 'Sports Nutrition',
                'description' => 'Sports nutrition document',
                'locale' => 'en',
            ],
            [self::$categories['sports']->getId(), self::$categories['health']->getId()],
            [self::$tags['fitness'], self::$tags['diet']],
            new \DateTime('2023-01-01 21:00:00'),
        );

        self::$media['business_tech'] = self::createAndEnhanceMedia(
            self::$collections['tech'],
            self::$mediaTypes['document'],
            [
                'title' => 'Zero Tech Investments',
                'description' => 'Tech investment document',
                'locale' => 'en',
            ],
            [self::$categories['business']->getId(), self::$categories['tech']->getId()],
            [self::$tags['startup'], self::$tags['cloud']],
            new \DateTime('2023-01-01 22:00:00'),
        );

        self::$media['entertainment_business'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['video'],
            [
                'title' => 'Entertainment Industry',
                'description' => 'Entertainment business video',
                'locale' => 'en',
            ],
            [self::$categories['entertainment']->getId(), self::$categories['business']->getId()],
            [self::$tags['movies'], self::$tags['finance']],
            new \DateTime('2023-01-01 23:00:00'),
        );

        self::$media['multi_category_multi_tag'] = self::createAndEnhanceMedia(
            self::$collections['main'],
            self::$mediaTypes['image'],
            [
                'title' => 'Digital Lifestyle',
                'description' => 'Digital lifestyle image',
                'locale' => 'en',
            ],
            [
                self::$categories['tech']->getId(),
                self::$categories['health']->getId(),
                self::$categories['entertainment']->getId(),
            ],
            [self::$tags['mobile'], self::$tags['fitness'], self::$tags['music']],
            new \DateTime('2023-01-02 00:00:00'),
        );

        $entityManager->flush();
    }

    public function testFindFlatByNoParameters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], []);

        $this->assertCount(15, $result);
        $count = $this->smartContentProvider->countBy([...$this->getDefaultFilters(), ...['locale' => 'en']]);
        $this->assertSame(15, $count);

        $resultIds = \array_map(
            fn ($media) => $media['id'],
            $result,
        );

        foreach (self::$media as $media) {
            $this->assertContains($media->getId(), $resultIds);
        }
    }

    /**
     * @return array<string, array{
     *     categoryKeys: string[],
     *     operator: 'OR'|'AND',
     *     expectedKeys: string[],
     *     expectedCount: int
     * }>
     */
    public static function categoryFilterProvider(): array
    {
        return [
            'single_category_OR' => [
                'categoryKeys' => ['tech'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'tech_health', 'business_tech', 'multi_category_multi_tag'],
                'expectedCount' => 5,
            ],
            'multiple_categories_OR' => [
                'categoryKeys' => ['tech', 'health'],
                'operator' => 'OR',
                'expectedKeys' => [
                    'tech1', 'tech2', 'health1', 'health2', 'tech_health',
                    'sports_health', 'business_tech', 'multi_category_multi_tag',
                ],
                'expectedCount' => 8,
            ],
            'single_category_AND' => [
                'categoryKeys' => ['health'],
                'operator' => 'AND',
                'expectedKeys' => ['health1', 'health2', 'tech_health', 'sports_health', 'multi_category_multi_tag'],
                'expectedCount' => 5,
            ],
            'multiple_categories_AND' => [
                'categoryKeys' => ['tech', 'health'],
                'operator' => 'AND',
                'expectedKeys' => ['tech_health', 'multi_category_multi_tag'],
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @param string[] $categoryKeys
     * @param 'AND'|'OR' $operator
     * @param string[] $expectedKeys
     */
    #[DataProvider('categoryFilterProvider')]
    public function testCategoryFiltering(array $categoryKeys, string $operator, array $expectedKeys, int $expectedCount): void
    {
        $categoryIds = \array_map(fn ($key) => self::$categories[$key]->getId(), $categoryKeys);

        /** @var 'AND'|'OR' $categoryOperator */
        $categoryOperator = $operator;

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => $categoryIds,
                'categoryOperator' => $categoryOperator,
            ],
        ], []);

        $this->assertCount($expectedCount, $result);
        $this->assertSame(
            $expectedCount,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => $categoryIds,
                    'categoryOperator' => $categoryOperator,
                ],
            ]),
        );

        $resultIds = \array_map(fn ($media) => $media['id'], $result);

        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$media[$key]->getId(), $resultIds, "Media '" . $key . "' should be in the result");
        }
    }

    /**
     * @return array<string, array{
     *     tagKeys: string[],
     *     operator: 'OR'|'AND',
     *     expectedKeys: string[],
     *     expectedCount: int
     * }>
     */
    public static function tagFilterProvider(): array
    {
        return [
            'single_tag_OR' => [
                'tagKeys' => ['mobile'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech_health', 'multi_category_multi_tag'],
                'expectedCount' => 3,
            ],
            'multiple_tags_OR' => [
                'tagKeys' => ['mobile', 'cloud'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'tech_health', 'business_tech', 'multi_category_multi_tag'],
                'expectedCount' => 5,
            ],
            'single_tag_AND' => [
                'tagKeys' => ['fitness'],
                'operator' => 'AND',
                'expectedKeys' => ['health1', 'health2', 'tech_health', 'sports_health', 'multi_category_multi_tag'],
                'expectedCount' => 5,
            ],
            'multiple_tags_AND' => [
                'tagKeys' => ['mobile', 'fitness'],
                'operator' => 'AND',
                'expectedKeys' => ['tech_health', 'multi_category_multi_tag'],
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @param string[] $tagKeys
     * @param 'AND'|'OR' $operator
     * @param string[] $expectedKeys
     */
    #[DataProvider('tagFilterProvider')]
    public function testTagFiltering(array $tagKeys, string $operator, array $expectedKeys, int $expectedCount): void
    {
        $tags = \array_map(fn ($key) => self::$tags[$key], $tagKeys);

        /** @var 'AND'|'OR' $tagOperator */
        $tagOperator = $operator;

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'tags' => $tags,
                'tagOperator' => $tagOperator,
            ],
        ], []);

        $this->assertCount($expectedCount, $result);
        $this->assertSame(
            $expectedCount,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'tags' => $tags,
                    'tagOperator' => $tagOperator,
                ],
            ]),
        );

        $resultIds = \array_map(fn ($media) => $media['id'], $result);

        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$media[$key]->getId(), $resultIds, "Media '" . $key . "' should be in the result");
        }
    }

    public function testFindFlatByCategoryAndTagFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['health']->getId()],
                'tags' => [self::$tags['fitness']],
            ],
        ], []);

        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => [self::$categories['health']->getId()],
                    'tags' => [self::$tags['fitness']],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($media) => $media['id'],
            $result,
        );

        $expectedKeys = ['health1', 'health2', 'tech_health', 'sports_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$media[$key]->getId(), $resultIds, "Media '" . $key . "' should be in the result");
        }
    }

    public function testFindFlatByLimitAndPageFirst(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'limit' => 5,
                'page' => 1,
            ],
        ], [
            'fileVersionMeta.title' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            15,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'limit' => 5,
                    'page' => 1,
                ],
            ]),
        );

        $this->assertSame('Cloud Computing', $result[0]['title']);
    }

    public function testFindFlatByMaxPerPageAndPageSecond(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'page' => 2,
                'maxPerPage' => 5,
            ],
        ], [
            'fileVersionMeta.title' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            15,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'page' => 2,
                    'maxPerPage' => 5,
                ],
            ]),
        );
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string[]}>
     */
    public static function sortingProvider(): array
    {
        return [
            'title_asc' => [
                ['fileVersionMeta.title' => 'asc'],
                ['tech2', 'multi_category_multi_tag', 'entertainment_business', 'business2', 'health1', 'sports1', 'health2', 'tech1', 'entertainment1', 'entertainment2', 'sports_health', 'business1', 'tech_health', 'sports2', 'business_tech'],
            ],
            'title_desc' => [
                ['fileVersionMeta.title' => 'desc'],
                ['business_tech', 'sports2', 'tech_health', 'business1', 'sports_health', 'entertainment2', 'entertainment1', 'tech1', 'health2', 'sports1', 'health1', 'business2', 'entertainment_business', 'multi_category_multi_tag', 'tech2'],
            ],
            'created_asc' => [
                ['media.created' => 'asc'],
                ['tech1', 'tech2', 'sports1', 'sports2', 'health1', 'health2', 'business1', 'business2', 'entertainment1', 'entertainment2', 'tech_health', 'sports_health', 'business_tech', 'entertainment_business', 'multi_category_multi_tag'],
            ],
        ];
    }

    /**
     * @param array<string, string> $sortBy
     * @param string[] $expectedOrder
     */
    #[DataProvider('sortingProvider')]
    public function testSorting(array $sortBy, array $expectedOrder, ?int $limit = null): void
    {
        $filters = [...$this->getDefaultFilters(), ...['locale' => 'en']];
        if (null !== $limit) {
            $filters['limitResult'] = $limit;
        }

        $result = $this->smartContentProvider->findFlatBy($filters, $sortBy);

        $actualKeys = $this->getMediaKeysFromResults($result);
        $this->assertSame($expectedOrder, $actualKeys);
    }

    /**
     * @return array<string, array{0: string, 1: string[], 2: int}>
     */
    public static function dataSourceProvider(): array
    {
        return [
            'tech_collection' => [
                'tech',
                ['tech1', 'tech2', 'business_tech'],
                3,
            ],
            'sports_collection' => [
                'sports',
                ['sports1', 'sports2', 'sports_health'],
                3,
            ],
            'main_collection' => [
                'main',
                [
                    'health1', 'health2', 'business1', 'business2',
                    'entertainment1', 'entertainment2', 'tech_health',
                    'entertainment_business', 'multi_category_multi_tag',
                ],
                9,
            ],
        ];
    }

    /**
     * @param string[] $expectedKeys
     */
    #[DataProvider('dataSourceProvider')]
    public function testDataSourceFiltering(string $dataSource, array $expectedKeys, int $expectedCount): void
    {
        $dataSourceId = (string) self::$collections[$dataSource]->getId();

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => $dataSourceId,
            ],
        ], []);

        $this->assertCount($expectedCount, $result);
        $actualKeys = $this->getMediaKeysFromResults($result);
        $this->assertSame($expectedKeys, $actualKeys);

        $this->assertSame(
            $expectedCount,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'dataSource' => $dataSourceId,
                ],
            ]),
        );
    }

    /**
     * @return array<string, array{0: string, 1: string[], 2: int}>
     */
    public static function typeFilterProvider(): array
    {
        return [
            'image_type' => [
                'image',
                ['tech1', 'sports1', 'health2', 'business2', 'tech_health', 'multi_category_multi_tag'],
                6,
            ],
            'video_type' => [
                'video',
                ['tech2', 'sports2', 'entertainment1', 'entertainment2', 'entertainment_business'],
                5,
            ],
            'document_type' => [
                'document',
                ['health1', 'business1', 'sports_health', 'business_tech'],
                4,
            ],
        ];
    }

    /**
     * @param string[] $expectedKeys
     */
    #[DataProvider('typeFilterProvider')]
    public function testTypeFiltering(string $mediaType, array $expectedKeys, int $expectedCount): void
    {
        $typeId = (string) self::$mediaTypes[$mediaType]->getId();

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'types' => [$typeId],
            ],
        ], []);

        $this->assertCount($expectedCount, $result);
        $actualKeys = $this->getMediaKeysFromResults($result);

        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $actualKeys, "Media '$key' should be in {$mediaType} type results");
        }

        $this->assertSame(
            $expectedCount,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'types' => [$typeId],
                ],
            ]),
        );
    }

    public function testFindFlatByCombinedFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => (string) self::$collections['main']->getId(),
                'types' => [(string) self::$mediaTypes['image']->getId()],
                'categories' => [self::$categories['health']->getId()],
                'categoryOperator' => 'OR',
            ],
        ], []);

        $this->assertCount(3, $result); // health2, tech_health, multi_category_multi_tag (all images in main collection with health category)
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'dataSource' => (string) self::$collections['main']->getId(),
                    'types' => [(string) self::$mediaTypes['image']->getId()],
                    'categories' => [self::$categories['health']->getId()],
                    'categoryOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($media) => $media['id'],
            $result,
        );

        $expectedKeys = ['health2', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$media[$key]->getId(), $resultIds, "Media '$key' should be in the combined filter result");
        }
        $this->assertCount(\count($expectedKeys), $resultIds);
    }

    /**
     * @param MediaData $data
     * @param int[] $categoryIds
     * @param string[] $tagNames
     */
    private static function createAndEnhanceMedia(
        CollectionInterface $collection,
        MediaType $mediaType,
        array $data = [],
        array $categoryIds = [],
        array $tagNames = [],
        ?\DateTimeInterface $createdAt = null,
    ): MediaInterface {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $media = self::createMedia($collection, $mediaType, $data);

        if (null !== $createdAt) {
            $immutableCreatedAt = $createdAt instanceof \DateTimeImmutable
                ? $createdAt
                : \DateTimeImmutable::createFromInterface($createdAt);
            $media->setCreated($immutableCreatedAt);
        }

        $file = $media->getFiles()->first();
        if ($file) {
            $fileVersion = $file->getFileVersions()->first();
            if ($fileVersion) {
                foreach ($categoryIds as $categoryId) {
                    /** @var CategoryInterface $category */
                    $category = $entityManager->getReference(CategoryInterface::class, $categoryId);
                    $fileVersion->addCategory($category);
                }

                foreach ($tagNames as $tagName) {
                    $tag = $entityManager->getRepository(Tag::class)
                        ->findOneBy(['name' => $tagName]);
                    if (!$tag) {
                        $tag = self::createTag(['name' => $tagName]);
                    }
                    $fileVersion->addTag($tag);
                }

                $entityManager->persist($fileVersion);
            }
        }

        $entityManager->flush();

        return $media;
    }

    /**
     * @return SmartContentBaseFilters
     */
    private function getDefaultFilters(): array
    {
        return [
            'categories' => [],
            'categoryOperator' => 'OR',
            'websiteCategories' => [],
            'websiteCategoryOperator' => 'OR',
            'tags' => [],
            'tagOperator' => 'OR',
            'websiteTags' => [],
            'websiteTagOperator' => 'OR',
            'types' => [],
            'typesOperator' => 'OR',
            'locale' => 'en',
            'webspaceKey' => null,
            'dataSource' => null,
            'limit' => null,
            'page' => 1,
            'maxPerPage' => null,
            'includeSubFolders' => false,
            'excludeDuplicates' => false,
        ];
    }

    /**
     * @param array<array{id: string}> $results
     *
     * @return string[]
     */
    private function getMediaKeysFromResults(array $results): array
    {
        $idToKeyMap = [];
        foreach (self::$media as $key => $media) {
            $idToKeyMap[(string) $media->getId()] = $key;
        }

        return \array_map(fn ($item) => $idToKeyMap[$item['id']], $results);
    }
}
