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

namespace Sulu\Content\Tests\Functional\Application\ContentResolver;

use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;

class ContentResolverTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateMediaTrait;
    use CreateTagTrait;

    private ContentResolverInterface $contentResolver;
    private ContentAggregatorInterface $contentAggregator;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentResolver = self::getContainer()->get('sulu_content.content_resolver');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');
    }

    // TODO add tests for
    // account selection / contact selection / image map / blocks 2 / excerpt / seo

    public function testResolveContentDefaultFields(): void
    {
        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'text_editor' => '<p>Lorem Ipsum dolor sit amet</p>',
                        'text_line' => 'Lorem Ipsum dolor sit amet',
                        'number' => 1337,
                        'phone' => '+49 123 456 789',
                        'single_select' => 'value-2',
                        'select' => [
                            'value-2',
                            'value-3',
                        ],
                        'checkbox' => true,
                        'color' => '#ff0000',
                        'time' => '13:37',
                        'date' => '2020-01-01',
                        'datetime' => '2020-01-01T13:37:00',
                        'email' => 'example@sulu.io',
                        'external_url' => 'https://sulu.io',
                        'text_area' => 'Lorem Ipsum dolor sit amet',
                        'excerpt' => [
                            'title' => 'excerpt-title-1',
                            'more' => 'excerpt-more-1',
                            'description' => 'excerpt-description-1',
                        ],
                        'seo' => [
                            'title' => 'seo-title-1',
                            'description' => 'seo-description-1',
                            'keywords' => 'seo-keywords-1',
                            'canonicalUrl' => 'https://sulu.io',
                        ],
                        'seoNoIndex' => true,
                        'seoNoFollow' => true,
                        'seoHideInSitemap' => true,
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        $excerpt = $result['extension']['excerpt'] ?? null;
        $seo = $result['extension']['seo'] ?? null;

        self::assertIsArray($excerpt);
        self::assertIsArray($seo);

        self::assertSame('Lorem Ipsum', $content['title']);
        self::assertSame('/lorem-ipsum', $content['url']);
        self::assertSame('<p>Lorem Ipsum dolor sit amet</p>', $content['text_editor']);
        self::assertSame('Lorem Ipsum dolor sit amet', $content['text_line']);
        self::assertSame(1337, $content['number']);
        self::assertSame('+49 123 456 789', $content['phone']);
        self::assertSame('value-2', $content['single_select']);
        self::assertSame(['value-2', 'value-3'], $content['select']);
        self::assertTrue($content['checkbox']);
        self::assertSame('#ff0000', $content['color']);
        self::assertSame('13:37', $content['time']);
        self::assertSame('2020-01-01', $content['date']);

        /** @var \DateTimeInterface|null $dateTime */
        $dateTime = $content['datetime'];
        self::assertSame(1577885820 /* 2020-01-01T13:37:00 */, $dateTime?->getTimestamp());
        self::assertSame('example@sulu.io', $content['email']);
        self::assertSame('https://sulu.io', $content['external_url']);
        self::assertSame('Lorem Ipsum dolor sit amet', $content['text_area']);

        // Excerpt
        self::assertSame('excerpt-title-1', $excerpt['title']);
        self::assertSame('excerpt-more-1', $excerpt['more']);
        self::assertSame('excerpt-description-1', $excerpt['description']);

        // Seo
        self::assertSame('seo-title-1', $seo['title']);
        self::assertSame('seo-description-1', $seo['description']);
        self::assertSame('seo-keywords-1', $seo['keywords']);
        self::assertSame('https://sulu.io', $seo['canonicalUrl']);
        self::assertTrue($seo['noIndex']);
        self::assertTrue($seo['noFollow']);
        self::assertTrue($seo['hideInSitemap']);
    }

    public function testResolveContentWithProperties(): void
    {
        $example0 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default-example-selection',
                        'title' => 'Nested example',
                        'url' => '/nested-example',
                        'description' => 'Nested example description',

                        'excerpt' => [
                            'title' => 'excerpt-example-title-0',
                            'description' => 'excerpt-example-description-0',
                        ],
                        'seo' => [
                            'title' => 'seo-example-title-0',
                            'description' => 'seo-example-description-0',
                        ],
                    ],
                ],
            ],
        );
        static::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default-example-selection',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'examples' => [$example0->getId()],
                        'examples_with_properties' => [$example0->getId()],
                        'excerpt' => [
                            'title' => 'excerpt-title-1',
                            'more' => 'excerpt-more-1',
                            'description' => 'excerpt-description-1',
                        ],
                        'seo' => [
                            'title' => 'seo-title-1',
                            'description' => 'seo-description-1',
                            'keywords' => 'seo-keywords-1',
                            'canonicalUrl' => 'https://sulu.io',
                        ],
                    ],
                    'seoNoIndex' => true,
                    'seoNoFollow' => true,
                    'seoHideInSitemap' => true,
                ],
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve(
            $dimensionContent,
            [
                'title' => 'title',
                'url' => 'url',
                'examplesWithProperties' => 'examples_with_properties',
                'examplesWithoutProperties' => 'examples',
            ],
        );

        self::assertEmpty($result['content']); // content is empty because all fields are extracted to root // TODO is this correct?

        // The result contains dynamically mapped properties at the root level
        // PHPStan doesn't know about these dynamic properties, so we suppress the warnings
        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame('Lorem Ipsum', $result['title']);
        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame('/lorem-ipsum', $result['url']);

        /** @var array<int, mixed> $examplesWithProperties */
        // @phpstan-ignore-next-line offsetAccess.notFound
        $examplesWithProperties = $result['examplesWithProperties'];
        self::assertCount(1, $examplesWithProperties);

        /** @var array<int, mixed> $examplesWithoutProperties */
        // @phpstan-ignore-next-line offsetAccess.notFound
        $examplesWithoutProperties = $result['examplesWithoutProperties'];
        self::assertCount(1, $examplesWithoutProperties);

        /** @var array<string, mixed> $exampleWithProperties */
        $exampleWithProperties = $examplesWithProperties[0];
        self::assertIsInt($exampleWithProperties['id']);
        self::assertSame('Nested example', $exampleWithProperties['title']);
        self::assertSame('Nested example description', $exampleWithProperties['description']);
        self::assertSame('excerpt-example-title-0', $exampleWithProperties['excerptTitle']);
        self::assertSame('excerpt-example-description-0', $exampleWithProperties['excerptDescription']);
        self::assertSame('seo-example-title-0', $exampleWithProperties['seoTitle']);
        self::assertSame('seo-example-description-0', $exampleWithProperties['seoDescription']);

        /** @var array<string, mixed> $exampleWithoutProperties */
        $exampleWithoutProperties = $examplesWithoutProperties[0];
        self::assertSame('Nested example', $exampleWithoutProperties['title']);
        self::assertSame('/nested-example', $exampleWithoutProperties['url']);
        self::assertSame('Nested example description', $exampleWithoutProperties['description']);
        self::assertNull($exampleWithoutProperties['image']);
        self::assertEmpty($exampleWithoutProperties['examples']);
        self::assertEmpty($exampleWithoutProperties['examples_with_properties']);
    }

    public function testResolveMedias(): void
    {
        // @phpstan-ignore-next-line
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $media1 = self::createMedia($collection1, ['title' => 'media-1', 'locale' => 'en']);
        $media2 = self::createMedia($collection1, ['title' => 'media-2', 'locale' => 'en']);
        $media3 = self::createMedia($collection1, ['title' => 'media-3', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'media_selection' => [
                            'ids' => [$media1->getId(), $media2->getId(), $media3->getId()],
                            'displayOption' => 'left',
                        ],
                        'single_media_selection' => [
                            'id' => $media1->getId(),
                            'displayOption' => 'left',
                        ],
                        'excerpt' => [
                            'icon' => [
                                'id' => $media1->getId(),
                            ],
                            'image' => [
                                'id' => $media2->getId(),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        $mediaSelection = $content['media_selection'];
        self::assertIsArray($mediaSelection);
        self::assertCount(3, $mediaSelection);
        $contentMedia1 = $mediaSelection[0];
        self::assertInstanceOf(Media::class, $contentMedia1);
        self::assertSame($media1->getId(), $contentMedia1->getId());
        $contentMedia2 = $mediaSelection[1];
        self::assertInstanceOf(Media::class, $contentMedia2);
        self::assertSame($media2->getId(), $contentMedia2->getId());
        $contentMedia3 = $mediaSelection[2];
        self::assertInstanceOf(Media::class, $contentMedia3);
        self::assertSame($media3->getId(), $contentMedia3->getId());

        $contentMedia1 = $content['single_media_selection'];
        self::assertInstanceOf(Media::class, $contentMedia1);
        self::assertSame($media1->getId(), $contentMedia1->getId());

        /** @var mixed[] $excerpt */
        $excerpt = $result['extension']['excerpt'];
        $contentMedia1 = $excerpt['icon'];
        self::assertInstanceOf(Media::class, $contentMedia1);
        self::assertSame($media1->getId(), $contentMedia1->getId());

        $contentMedia2 = $excerpt['image'];
        self::assertInstanceOf(Media::class, $contentMedia2);
        self::assertSame($media2->getId(), $contentMedia2->getId());

        $view = $result['view'];
        /** @var array{ids: int[], displayOption: string|null} $mediaSelectionView */
        $mediaSelectionView = $view['media_selection'];
        self::assertSame('left', $mediaSelectionView['displayOption']);
        self::assertSame([$media1->getId(), $media2->getId(), $media3->getId()], $mediaSelectionView['ids']);

        /** @var array{id: int|null, displayOption: string|null} $singleMediaSelectionView */
        $singleMediaSelectionView = $view['single_media_selection'];
        self::assertSame('left', $singleMediaSelectionView['displayOption']);
        self::assertSame($media1->getId(), $singleMediaSelectionView['id']);
    }

    public function testResolveTeaserSelection(): void
    {
        $example1 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Example 1',
                    'url' => '/example-1',
                    'description' => 'First example description',
                ],
            ],
        ]);

        $example2 = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Example 2',
                    'url' => '/example-2',
                    'description' => 'Second example description',
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-teaser',
                    'title' => 'Teaser Page',
                    'url' => '/teaser-page',
                    'teasers' => [
                        'presentAs' => 'two-columns',
                        'items' => [
                            ['id' => (string) $example1->getId(), 'type' => 'examples'],
                            ['id' => (string) $example2->getId(), 'type' => 'examples'],
                        ],
                    ],
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];
        self::assertArrayHasKey('teasers', $content);
        $teasers = $content['teasers'];
        self::assertIsArray($teasers);
        self::assertCount(2, $teasers);

        $teaser1 = $teasers[0];
        self::assertInstanceOf(Teaser::class, $teaser1);
        self::assertSame('Example 1', $teaser1->getTitle());

        $teaser2 = $teasers[1];
        self::assertInstanceOf(Teaser::class, $teaser2);
        self::assertSame('Example 2', $teaser2->getTitle());

        $view = $result['view'];
        /** @var array{presentAs: string|null, items: array<int, array<string, mixed>>} $teasersView */
        $teasersView = $view['teasers'];
        self::assertSame('two-columns', $teasersView['presentAs']);
        self::assertCount(2, $teasersView['items']);
    }

    public function testResolveCollections(): void
    {
        // @phpstan-ignore-next-line
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $collection2 = self::createCollection([
            'title' => 'collection-2',
            'locale' => 'en',
            'name' => 'collection-2',
            'key' => 'collection-2',
        ]);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'collection_selection' => [$collection1->getId(), $collection2->getId()],
                        'single_collection_selection' => $collection1->getId(),
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        $contentSelection = $content['collection_selection'];
        self::assertIsArray($contentSelection);
        self::assertCount(2, $contentSelection);
        $contentCollection1 = $contentSelection[0];
        self::assertInstanceOf(Collection::class, $contentCollection1);
        self::assertSame($collection1->getId(), $contentCollection1->getId());
        $contentCollection2 = $contentSelection[1];
        self::assertInstanceOf(Collection::class, $contentCollection2);
        self::assertSame($collection2->getId(), $contentCollection2->getId());

        $singleCollectionSelection = $content['single_collection_selection'];
        self::assertInstanceOf(Collection::class, $singleCollectionSelection);
        self::assertSame($collection1->getId(), $singleCollectionSelection->getId());
    }

    public function testResolveCategories(): void
    {
        // @phpstan-ignore-next-line
        $category1 = self::createCategory(['key' => 'category-1']);
        $category2 = self::createCategory(['key' => 'category-2']);
        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'category_selection' => [$category1->getId(), $category2->getId()],
                        'single_category_selection' => $category1->getId(),
                        'excerptCategories' => [$category1->getId(), $category2->getId()],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        $categorySelection = $content['category_selection'];
        self::assertIsArray($categorySelection);
        self::assertCount(2, $categorySelection);

        $contentCategory1 = $categorySelection[0];
        self::assertInstanceOf(CategoryWrapper::class, $contentCategory1);
        self::assertSame($category1->getId(), $contentCategory1->getId());
        $contentCategory2 = $categorySelection[1];
        self::assertInstanceOf(CategoryWrapper::class, $contentCategory2);
        self::assertSame($category2->getId(), $contentCategory2->getId());

        $singleCategorySelection = $content['single_category_selection'];
        self::assertInstanceOf(CategoryWrapper::class, $singleCategorySelection);
        self::assertSame($category1->getId(), $singleCategorySelection->getId());

        /** @var mixed[] $excerpt */
        $excerpt = $result['extension']['excerpt'];
        $excerptCategories = $excerpt['categories'];
        self::assertIsArray($excerptCategories);
        self::assertCount(2, $excerptCategories);
        $excerptCategory1 = $excerptCategories[0];
        self::assertInstanceOf(CategoryWrapper::class, $excerptCategory1);
        self::assertSame($category1->getId(), $excerptCategory1->getId());
        $excerptCategory2 = $excerptCategories[1];
        self::assertInstanceOf(CategoryWrapper::class, $excerptCategory2);
        self::assertSame($category2->getId(), $excerptCategory2->getId());
    }

    public function testResolveTags(): void
    {
        $tag1 = self::createTag(['name' => 'tag-1']);
        $tag2 = self::createTag(['name' => 'tag-2']);
        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'tag_selection' => [$tag1->getId()],
                        'excerptTags' => [$tag1->getId(), $tag2->getId()],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        $excerpt = $result['extension']['excerpt'];

        $tagSelection = $content['tag_selection'];
        self::assertIsArray($tagSelection);
        self::assertSame('tag-1', $tagSelection[0]);

        $excerptTags = $excerpt['tags'];
        self::assertIsArray($excerptTags);
        self::assertSame('tag-1', $excerptTags[0]);
        self::assertSame('tag-2', $excerptTags[1]);
    }

    public function testResolveContentBlocks(): void
    {
        // @phpstan-ignore-next-line
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $media1 = self::createMedia($collection1, ['title' => 'media-1', 'locale' => 'en']);
        $media2 = self::createMedia($collection1, ['title' => 'media-2', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'blocks' => [
                            [
                                'type' => 'editor',
                                'text_editor' => '<p>Block Level 0: Lorem Ipsum dolor sit amet</p>',
                            ],
                            [
                                'type' => 'media',
                                'media_selection' => [
                                    'ids' => [$media1->getId()],
                                ],
                            ],
                            [
                                'type' => 'block',
                                'blocks' => [
                                    [
                                        'type' => 'editor',
                                        'text_editor' => '<p>Block Level 1: Lorem Ipsum dolor sit amet</p>',
                                    ],
                                    [
                                        'type' => 'media',
                                        'media_selection' => [
                                            'ids' => [$media1->getId(), $media2->getId()],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var array{content: array{title: string, url: string, blocks: array<int, mixed>}, view: array{blocks: array<int, mixed>}} $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{title: string, url: string, blocks: array<int, mixed>} $content */
        $content = $result['content'];
        self::assertSame('Lorem Ipsum', $content['title']);
        self::assertSame('/lorem-ipsum', $content['url']);

        /** @var array{blocks: array<int, mixed>} $view */
        $view = $result['view'];
        /** @var array<int, mixed> $viewBlocks */
        $viewBlocks = $view['blocks'];
        self::assertCount(3, $viewBlocks);

        // block 0
        /** @var array{type: string, text_editor: string} $block0 */
        $block0 = $content['blocks'][0];
        self::assertSame('editor', $block0['type']);
        self::assertSame('<p>Block Level 0: Lorem Ipsum dolor sit amet</p>', $block0['text_editor']);

        /** @var array{text_editor: mixed} $viewBlock0 */
        $viewBlock0 = $viewBlocks[0];
        self::assertSame([], $viewBlock0['text_editor']);

        // block 1
        /** @var array{type: string, media_selection: mixed[]} $block1 */
        $block1 = $content['blocks'][1];
        self::assertSame('media', $block1['type']);
        $mediaSelection = $block1['media_selection'];
        self::assertCount(1, $mediaSelection);
        $mediaApi1 = $mediaSelection[0];
        self::assertInstanceOf(Media::class, $mediaApi1);
        self::assertSame($media1->getId(), $mediaApi1->getId());

        /** @var array{media_selection: array{ids: mixed[], displayOption: mixed}} $viewBlock1 */
        $viewBlock1 = $viewBlocks[1];
        $mediaSelectionView = $viewBlock1['media_selection'];
        self::assertSame([$media1->getId()], $mediaSelectionView['ids']);
        self::assertNull($mediaSelectionView['displayOption']);

        // block 2
        /** @var array{type: string, blocks: array<int, mixed>} $block2 */
        $block2 = $content['blocks'][2];
        self::assertSame('block', $block2['type']);
        /** @var array{blocks: array<int, mixed>} $viewBlock2 */
        $viewBlock2 = $viewBlocks[2];
        /** @var array<int, mixed> $viewBlocks2 */
        $viewBlocks2 = $viewBlock2['blocks'];

        /** @var array{type: string, text_editor: string} $block2_0 */
        $block2_0 = $block2['blocks'][0];
        self::assertSame('<p>Block Level 1: Lorem Ipsum dolor sit amet</p>', $block2_0['text_editor']);
        self::assertSame('editor', $block2_0['type']);
        /** @var array{text_editor: mixed} $viewBlock2_0 */
        $viewBlock2_0 = $viewBlocks2[0];
        self::assertSame([], $viewBlock2_0['text_editor']);

        /** @var array{type: string, media_selection: mixed[]} $block2_1 */
        $block2_1 = $block2['blocks'][1];
        self::assertSame('media', $block2_1['type']);
        $mediaSelection = $block2_1['media_selection'];
        self::assertCount(2, $mediaSelection);
        $mediaApi1 = $block2_1['media_selection'][0];
        self::assertInstanceOf(Media::class, $mediaApi1);
        self::assertSame($media1->getId(), $mediaApi1->getId());
        $mediaApi2 = $block2_1['media_selection'][1];
        self::assertInstanceOf(Media::class, $mediaApi2);
        self::assertSame($media2->getId(), $mediaApi2->getId());

        /** @var array{media_selection: array{ids: mixed[], displayOption: mixed}} $viewBlock2_1 */
        $viewBlock2_1 = $viewBlocks2[1];
        $mediaSelectionView = $viewBlock2_1['media_selection'];
        self::assertSame([$media1->getId(), $media2->getId()], $mediaSelectionView['ids']);
        self::assertNull($mediaSelectionView['displayOption']);
    }

    public function testResolvePropertyNamedContentView(): void
    {
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $media1 = self::createMedia($collection1, ['title' => 'media-1', 'locale' => 'en']);
        $media2 = self::createMedia($collection1, ['title' => 'media-2', 'locale' => 'en']);
        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default-content-view-properties',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => '<p>Content text 2</p>',
                            ],
                            [
                                'type' => 'text',
                                'text' => '<p>Content text 2</p>',
                            ],
                            [
                                'type' => 'media',
                                'media' => [
                                    'ids' => [$media1->getId()],
                                ],
                            ],
                        ],
                        'view' => [
                            [
                                'type' => 'text',
                                'text' => '<p>View text 2</p>',
                            ],
                            [
                                'type' => 'text',
                                'text' => '<p>View text 2</p>',
                            ],
                            [
                                'type' => 'media',
                                'media' => [
                                    'ids' => [$media2->getId()],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);

        /** @var array{content: array{title: string, url: string, content: array<int, mixed>, view: array<int, mixed>}, view: array{content: array<int, mixed>, view: array<int, mixed>}} $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{title: string, url: string, content: array<int, mixed>, view: array<int, mixed>} $content */
        $content = $result['content'];
        self::assertSame('Lorem Ipsum', $content['title']);
        self::assertSame('/lorem-ipsum', $content['url']);

        // content.content blocks
        /** @var array<int, mixed> $contentBlocks */
        $contentBlocks = $content['content'];
        self::assertCount(3, $contentBlocks);
        /** @var array{type: string, text?: string, media?: mixed[]} $contentBlock0 */
        $contentBlock0 = $contentBlocks[0];
        self::assertSame('text', $contentBlock0['type']);
        self::assertArrayHasKey('text', $contentBlock0);
        self::assertSame('<p>Content text 2</p>', $contentBlock0['text']);
        /** @var array{type: string, text?: string, media?: mixed[]} $contentBlock1 */
        $contentBlock1 = $contentBlocks[1];
        self::assertSame('text', $contentBlock1['type']);
        self::assertArrayHasKey('text', $contentBlock1);
        self::assertSame('<p>Content text 2</p>', $contentBlock1['text']);
        /** @var array{type: string, media: mixed[]} $contentBlock2 */
        $contentBlock2 = $contentBlocks[2];
        self::assertSame('media', $contentBlock2['type']);
        self::assertCount(1, $contentBlock2['media']);
        $contentMedia = $contentBlock2['media'][0];
        self::assertInstanceOf(Media::class, $contentMedia);
        self::assertSame($media1->getId(), $contentMedia->getId());

        // content.view blocks
        /** @var array<int, mixed> $contentViewBlocks */
        $contentViewBlocks = $content['view'];
        self::assertCount(3, $contentViewBlocks);
        /** @var array{type: string, text?: string, media?: mixed[]} $contentViewBlock0 */
        $contentViewBlock0 = $contentViewBlocks[0];
        self::assertSame('text', $contentViewBlock0['type']);
        self::assertArrayHasKey('text', $contentViewBlock0);
        self::assertSame('<p>View text 2</p>', $contentViewBlock0['text']);
        /** @var array{type: string, text?: string, media?: mixed[]} $contentViewBlock1 */
        $contentViewBlock1 = $contentViewBlocks[1];
        self::assertSame('text', $contentViewBlock1['type']);
        self::assertArrayHasKey('text', $contentViewBlock1);
        self::assertSame('<p>View text 2</p>', $contentViewBlock1['text']);
        /** @var array{type: string, media: mixed[]} $contentViewBlock2 */
        $contentViewBlock2 = $contentViewBlocks[2];
        self::assertSame('media', $contentViewBlock2['type']);
        self::assertCount(1, $contentViewBlock2['media']);
        $contentViewMedia = $contentViewBlock2['media'][0];
        self::assertInstanceOf(Media::class, $contentViewMedia);
        self::assertSame($media2->getId(), $contentViewMedia->getId());

        // root view mapping (ids/displayOption); ignore metadata
        /** @var array{content: array<int, mixed>, view: array<int, mixed>, title: array<mixed>, url: array<mixed>} $view */
        $view = $result['view'];
        self::assertSame([], $view['title']);
        self::assertSame([], $view['url']);

        /** @var array<int, mixed> $viewContentBlocks */
        $viewContentBlocks = $view['content'];
        self::assertCount(3, $viewContentBlocks);
        /** @var array{text: mixed} $viewContentBlock0 */
        $viewContentBlock0 = $viewContentBlocks[0];
        self::assertSame([], $viewContentBlock0['text']);
        /** @var array{text: mixed} $viewContentBlock1 */
        $viewContentBlock1 = $viewContentBlocks[1];
        self::assertSame([], $viewContentBlock1['text']);
        /** @var array{media: array{ids: mixed[], displayOption: mixed}} $viewContentBlock2 */
        $viewContentBlock2 = $viewContentBlocks[2];
        self::assertSame([$media1->getId()], $viewContentBlock2['media']['ids']);
        self::assertNull($viewContentBlock2['media']['displayOption']);

        /** @var array<int, mixed> $viewViewBlocks */
        $viewViewBlocks = $view['view'];
        self::assertCount(3, $viewViewBlocks);
        /** @var array{text: mixed} $viewViewBlock0 */
        $viewViewBlock0 = $viewViewBlocks[0];
        self::assertSame([], $viewViewBlock0['text']);
        /** @var array{text: mixed} $viewViewBlock1 */
        $viewViewBlock1 = $viewViewBlocks[1];
        self::assertSame([], $viewViewBlock1['text']);
        /** @var array{media: array{ids: mixed[], displayOption: mixed}} $viewViewBlock2 */
        $viewViewBlock2 = $viewViewBlocks[2];
        self::assertSame([$media2->getId()], $viewViewBlock2['media']['ids']);
        self::assertNull($viewViewBlock2['media']['displayOption']);
    }

    public function testResolveContentBlocksWithSettings(): void
    {
        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'blocks' => [
                            [
                                'type' => 'editor',
                                'text_editor' => '<p>Editor block with settings</p>',
                                'settings' => [
                                    'hidden' => false,
                                    'schedules_enabled' => false,
                                    'schedules' => [
                                        [
                                            'type' => 'fixed',
                                            'start' => '2025-09-17T00:00:00',
                                            'end' => '2025-09-25T00:00:00',
                                        ],
                                    ],
                                    'segment_enabled' => null,
                                    'segments' => null,
                                    'target_groups_enabled' => null,
                                    'target_groups' => null,
                                ],
                            ],
                            [
                                'type' => 'editor',
                                'text_editor' => '<p>Editor block without settings</p>',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        self::assertArrayHasKey('blocks', $content);
        $blocks = $content['blocks'];
        self::assertIsArray($blocks);
        self::assertCount(2, $blocks);

        // First block with settings
        /** @var array<string, mixed> $blockWithSettings */
        $blockWithSettings = $blocks[0];
        self::assertSame('editor', $blockWithSettings['type']);
        self::assertSame('<p>Editor block with settings</p>', $blockWithSettings['text_editor']);

        // Assert settings are resolved
        self::assertArrayHasKey('settings', $blockWithSettings);
        $settings = $blockWithSettings['settings'];
        self::assertIsArray($settings);
        self::assertFalse($settings['hidden']);
        self::assertFalse($settings['schedules_enabled']);

        self::assertArrayHasKey('schedules', $settings);
        self::assertIsArray($settings['schedules']);
        self::assertCount(1, $settings['schedules']);

        /** @var array{type: string, start: \DateTimeInterface, end: \DateTimeInterface} $schedule */
        $schedule = $settings['schedules'][0];
        self::assertSame('fixed', $schedule['type']);
        self::assertSame((new \DateTimeImmutable('2025-09-17T00:00:00'))->getTimestamp(), $schedule['start']->getTimestamp());
        self::assertSame((new \DateTimeImmutable('2025-09-25T00:00:00'))->getTimestamp(), $schedule['end']->getTimestamp());

        self::assertNull($settings['segment_enabled']);
        self::assertNull($settings['segments']);
        self::assertNull($settings['target_groups_enabled']);
        self::assertNull($settings['target_groups']);

        // Second block without settings
        /** @var array<string, mixed> $blockWithoutSettings */
        $blockWithoutSettings = $blocks[1];
        self::assertSame('editor', $blockWithoutSettings['type']);
        self::assertSame('<p>Editor block without settings</p>', $blockWithoutSettings['text_editor']);
        self::assertArrayNotHasKey('settings', $blockWithoutSettings);
    }

    public function testResolveContentBlocksWithCustomSettings(): void
    {
        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Referenced Example 1',
                        'url' => '/referenced-example-1',
                        'description' => 'This example will be referenced in block settings',
                    ],
                ],
            ],
        );

        $example2 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Referenced Example 2',
                        'url' => '/referenced-example-2',
                        'description' => 'Another example for multi-selection testing',
                    ],
                ],
            ],
        );

        static::getEntityManager()->flush();

        $exampleWithCustomSettings = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'blocks-with-custom-settings',
                        'title' => 'Content with Custom Block Settings',
                        'url' => '/custom-settings-test',
                        'blocks_with_custom_settings_form_key' => [
                            [
                                'type' => 'editor',
                                'text_editor' => '<p>Block with custom settings and entity references</p>',
                                'settings' => [
                                    'hidden' => false,
                                    'custom_note' => 'This is a test note',
                                    'examples' => [$example1->getId(), $example2->getId()],
                                ],
                            ],
                            [
                                'type' => 'editor',
                                'text_editor' => '<p>Block without any settings</p>',
                            ],
                        ],
                    ],
                ],
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($exampleWithCustomSettings, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        self::assertArrayHasKey('blocks_with_custom_settings_form_key', $content);
        $blocks = $content['blocks_with_custom_settings_form_key'];
        self::assertIsArray($blocks);
        self::assertCount(2, $blocks);

        // Test first block with custom settings
        /** @var array<string, mixed> $blockWithCustomSettings */
        $blockWithCustomSettings = $blocks[0];
        self::assertSame('editor', $blockWithCustomSettings['type']);
        self::assertSame('<p>Block with custom settings and entity references</p>', $blockWithCustomSettings['text_editor']);

        self::assertArrayHasKey('settings', $blockWithCustomSettings);
        $settings = $blockWithCustomSettings['settings'];
        self::assertIsArray($settings);

        self::assertFalse($settings['hidden']);
        self::assertSame('This is a test note', $settings['custom_note']);

        self::assertArrayHasKey('examples', $settings);
        $examples = $settings['examples'];
        self::assertIsArray($examples);
        self::assertCount(2, $examples);

        /** @var array<string, mixed> $resolvedExample1 */
        $resolvedExample1 = $examples[0];
        self::assertSame('Referenced Example 1', $resolvedExample1['title']);
        self::assertSame('This example will be referenced in block settings', $resolvedExample1['description']);

        /** @var array<string, mixed> $resolvedExample2 */
        $resolvedExample2 = $examples[1];
        self::assertSame('Referenced Example 2', $resolvedExample2['title']);
        self::assertSame('Another example for multi-selection testing', $resolvedExample2['description']);

        // Test second block without settings
        /** @var array<string, mixed> $blockWithoutSettings */
        $blockWithoutSettings = $blocks[1];
        self::assertSame('editor', $blockWithoutSettings['type']);
        self::assertSame('<p>Block without any settings</p>', $blockWithoutSettings['text_editor']);
        self::assertArrayNotHasKey('settings', $blockWithoutSettings);
    }

    public function testResolveImageMap(): void
    {
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $mainMedia = self::createMedia($collection1, ['title' => 'media-main', 'locale' => 'en']);
        $media1 = self::createMedia($collection1, ['title' => 'media-1', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'image_map' => [
                            'imageId' => $mainMedia->getId(),
                            'hotspots' => [
                                [
                                    'type' => 'basic',
                                    'hotspot' => [
                                        'type' => 'circle',
                                        'left' => 0.5052987808664333,
                                        'top' => 0.5940029375917998,
                                        'radius' => 0.09,
                                    ],
                                    'title' => 'Basic title',
                                    'description' => 'Basic description',
                                ],
                                [
                                    'type' => 'advanced',
                                    'hotspot' => [
                                        'type' => 'rectangle',
                                        'width' => 1,
                                        'height' => 0.20797524922653826,
                                        'left' => 0,
                                        'top' => 0.7920247507734617,
                                    ],
                                    'text' => '<p>Advanced <b>ckeditor</b> text</p>',
                                    'media' => [
                                        'ids' => [$media1->getId()],
                                        'displayOption' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        // Assert that image_map exists in content
        self::assertArrayHasKey('image_map', $content);
        $imageMap = $content['image_map'];
        self::assertIsArray($imageMap);
        self::assertCount(2, $imageMap);

        // Assert the main image
        self::assertArrayHasKey('image', $imageMap);
        self::assertInstanceOf(Media::class, $imageMap['image']);
        self::assertSame($mainMedia->getId(), $imageMap['image']->getId());

        // Assert hotspots structure
        self::assertArrayHasKey('hotspots', $imageMap);
        $hotspots = $imageMap['hotspots'];
        self::assertIsArray($hotspots);
        self::assertCount(2, $hotspots);

        // Assert basic hotspot
        /** @var array<string, mixed> $basicHotspot */
        $basicHotspot = $hotspots[0];
        self::assertSame('basic', $basicHotspot['type']);
        self::assertArrayHasKey('hotspot', $basicHotspot);
        self::assertIsArray($basicHotspot['hotspot']);

        $basicHotspotData = $basicHotspot['hotspot'];
        self::assertSame('circle', $basicHotspotData['type']);
        self::assertSame(0.5052987808664333, $basicHotspotData['left']);
        self::assertSame(0.5940029375917998, $basicHotspotData['top']);
        self::assertSame(0.09, $basicHotspotData['radius']);
        self::assertSame('Basic title', $basicHotspot['title']);
        self::assertSame('Basic description', $basicHotspot['description']);

        // Assert advanced hotspot
        /** @var array<string, mixed> $advancedHotspot */
        $advancedHotspot = $hotspots[1];
        self::assertSame('advanced', $advancedHotspot['type']);
        self::assertArrayHasKey('hotspot', $advancedHotspot);
        self::assertIsArray($advancedHotspot['hotspot']);

        $advancedHotspotData = $advancedHotspot['hotspot'];
        self::assertSame('rectangle', $advancedHotspotData['type']);
        self::assertSame(1, $advancedHotspotData['width']);
        self::assertSame(0.20797524922653826, $advancedHotspotData['height']);
        self::assertSame(0, $advancedHotspotData['left']);
        self::assertSame(0.7920247507734617, $advancedHotspotData['top']);
        self::assertSame('<p>Advanced <b>ckeditor</b> text</p>', $advancedHotspot['text']);

        // Assert advanced hotspot media
        self::assertArrayHasKey('media', $advancedHotspot);
        $advancedMedia = $advancedHotspot['media'];
        self::assertIsArray($advancedMedia);
        self::assertCount(1, $advancedMedia);
        self::assertInstanceOf(Media::class, $advancedMedia[0]);
        self::assertSame($media1->getId(), $advancedMedia[0]->getId());
    }

    public function testResolveLinkExternal(): void
    {
        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'link' => [
                            'provider' => 'external',
                            'href' => 'https://sulu.io',
                            'title' => 'Sulu Website',
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        self::assertArrayHasKey('link', $content);
        self::assertSame('https://sulu.io', $content['link']);
    }

    public function testResolveLinkInternal(): void
    {
        $linkedExample = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Linked Example',
                        'url' => '/linked-example',
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'link' => [
                            'provider' => 'examples',
                            'href' => $linkedExample->getId(),
                            'title' => 'Linked Example',
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ],
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];

        self::assertArrayHasKey('link', $content);
        $link = $content['link'];
        self::assertSame('/linked-example', $link);
    }

    public function testResolveNestedContentWithDraftStage(): void
    {
        $nestedExample = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Nested Example Live',
                        'url' => '/nested-example',
                        'description' => 'Nested content live description',
                    ],
                    'draft' => [
                        'template' => 'default',
                        'title' => 'Nested Example Draft',
                        'url' => '/nested-example',
                        'description' => 'Nested content draft description',
                    ],
                ],
            ],
        );
        static::getEntityManager()->flush();

        $mainExample = static::createExample(
            [
                'en' => [
                    'draft' => [
                        'template' => 'default-example-selection',
                        'title' => 'Main Example',
                        'url' => '/main-example',
                        'examples' => [$nestedExample->getId()],
                    ],
                ],
            ],
        );
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        /** @var ExampleRepository $exampleRepository */
        $exampleRepository = static::getContainer()->get('example_test.example_repository');

        // We need to refetch the main example to have the correct dimension content loaded
        $mainExample = $exampleRepository->findOneBy(
            [
                'id' => $mainExample->getId(),
                'locale' => 'en',
                'stage' => 'draft',
            ],
            [
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ],
        );
        self::assertNotNull($mainExample);

        $dimensionContent = $this->contentAggregator->aggregate($mainExample, ['locale' => 'en', 'stage' => 'draft']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];
        self::assertArrayHasKey('examples', $content);
        $examples = $content['examples'];
        self::assertIsArray($examples);
        self::assertCount(1, $examples);

        $resolvedNested = $examples[0];
        self::assertIsArray($resolvedNested);
        self::assertSame('Nested Example Live', $resolvedNested['title']);
        self::assertSame('Nested content live description', $resolvedNested['description']);
    }
}
