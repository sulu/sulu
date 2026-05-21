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

use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer as SuluRequestAnalyzer;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Symfony\Component\HttpFoundation\Request;

class SmartContentContentResolverTest extends SuluTestCase
{
    use CreateExampleTrait;
    use CreateCategoryTrait;
    use CreateTagTrait;

    private ContentResolverInterface $contentResolver;
    private ContentAggregatorInterface $contentAggregator;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentResolver = self::getContainer()->get('sulu_content.content_resolver');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');
    }

    /**
     * @param array<string, string> $query
     */
    private function pushWebsiteRequest(array $query = []): void
    {
        $request = Request::create('http://localhost/en', 'GET', $query);
        $webspace = new Webspace();
        $webspace->setKey('website');
        $request->attributes->set(SuluRequestAnalyzer::SULU_ATTRIBUTE, new RequestAttributes([
            'webspace' => $webspace,
        ]));
        self::getContainer()->get('request_stack')->push($request);
    }

    public function testResolveExampleSmartContentWithoutProperties(): void
    {
        // create one matching example item
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Example 0',
                    'url' => '/example-0',
                    'description' => 'Example 0 description',
                ],
            ],
        ], ['create_route' => true]);
        static::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default-example-smart-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'examples' => [
                            'types' => ['default'],
                        ],
                    ],
                ],
            ]
        );
        static::getEntityManager()->flush();

        // Ensure SmartContentPropertyResolver has a current request with required webspace
        $request = Request::create('http://localhost/en');
        $webspace = new Webspace();
        $webspace->setKey('website');
        $request->attributes->set(SuluRequestAnalyzer::SULU_ATTRIBUTE, new RequestAttributes([
            'webspace' => $webspace,
        ]));
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];
        self::assertArrayHasKey('examples', $content);
        /** @var array<int, array<string, mixed>> $examples */
        $examples = $content['examples'];
        self::assertCount(1, $examples);

        /** @var array<string, mixed> $example */
        $example = $examples[0];
        self::assertSame('Example 0', $example['title']);
        self::assertSame('/example-0', $example['url']);
        self::assertSame('Example 0 description', $example['description']);

        // Check view information for smart content
        /** @var array<string, mixed> $view */
        $view = $result['view'];
        self::assertArrayHasKey('examples', $view);
        /** @var array{page: int, paginated: bool, hasNextPage: bool|null, limit: int|null, types: array<int, string>} $examplesView */
        $examplesView = $view['examples'];
        self::assertSame(1, $examplesView['page']);
        self::assertFalse($examplesView['paginated']);
        self::assertFalse($examplesView['hasNextPage']);
        self::assertNull($examplesView['limit']);
        self::assertSame(['default'], $examplesView['types']);
    }

    public function testResolveExampleSmartContentWithProperties(): void
    {
        $category = self::createCategory(['key' => 'test-cat']);
        $tag = self::createTag(['name' => 'test-tag']);
        static::getEntityManager()->flush();

        $example0 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default',
                        'title' => 'Example 0',
                        'url' => '/example-0',
                        'description' => 'Example 0 description',
                        'excerpt' => [
                            'title' => 'excerpt-example-title-0',
                            'description' => 'excerpt-example-description-0',
                        ],
                        'excerptCategories' => [$category->getId()],
                        'excerptTags' => [$tag->getId()],
                        'seo' => [
                            'title' => 'seo-example-title-0',
                            'description' => 'seo-example-description-0',
                        ],
                    ],
                ],
            ]
        );
        static::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'default-example-smart-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'examples_with_properties' => [
                            'types' => ['default'],
                        ],
                    ],
                ],
            ]
        );
        static::getEntityManager()->flush();

        // Ensure SmartContentPropertyResolver has a current request with required webspace
        $request = Request::create('http://localhost/en');
        $webspace = new Webspace();
        $webspace->setKey('website');
        $request->attributes->set(SuluRequestAnalyzer::SULU_ATTRIBUTE, new RequestAttributes([
            'webspace' => $webspace,
        ]));
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];
        self::assertArrayHasKey('examples_with_properties', $content);
        /** @var array<int, array<string, mixed>> $examplesWithProps */
        $examplesWithProps = $content['examples_with_properties'];
        self::assertCount(1, $examplesWithProps);

        /** @var array{id: int, title: string, description: string, excerptTitle: string, excerptDescription: string, excerptCategories: array<int, CategoryInterface>, excerptTags: array<int, string>, seoTitle: string, seoDescription: string} $example */
        $example = $examplesWithProps[0];
        self::assertSame($example0->getId(), $example['id']);
        self::assertSame('Example 0', $example['title']);
        self::assertSame('Example 0 description', $example['description']);
        self::assertSame('excerpt-example-title-0', $example['excerptTitle']);
        self::assertSame('excerpt-example-description-0', $example['excerptDescription']);
        self::assertSame('seo-example-title-0', $example['seoTitle']);
        self::assertSame('seo-example-description-0', $example['seoDescription']);
        self::assertCount(1, $example['excerptCategories']);
        self::assertCount(1, $example['excerptTags']);
        self::assertSame($category->getId(), $example['excerptCategories'][0]->getId());
        self::assertSame($tag->getName(), $example['excerptTags'][0]);

        /** @var array<string, mixed> $view */
        $view = $result['view'];
        self::assertArrayHasKey('examples_with_properties', $view);
        /** @var array{page: int, paginated: bool, hasNextPage: bool|null, limit: int|null, types: array<int, string>} $examplesView */
        $examplesView = $view['examples_with_properties'];
        self::assertSame(1, $examplesView['page']);
        self::assertFalse($examplesView['paginated']);
        self::assertFalse($examplesView['hasNextPage']);
        self::assertNull($examplesView['limit']);
        self::assertSame(['default'], $examplesView['types']);
    }

    public function testResolveExampleSmartContentWithLimitPaginationSortAndTypes(): void
    {
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Alpha',
                    'url' => '/alpha',
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Bravo',
                    'url' => '/bravo',
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Charlie',
                    'url' => '/charlie',
                ],
            ],
        ]);

        // non-matching type
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'example-2',
                    'title' => 'Delta',
                    'url' => '/delta',
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Listing',
                    'url' => '/listing',
                    'examples' => [
                        'types' => ['default'],
                        'sortBy' => 'title',
                        'sortMethod' => 'ASC',
                        'limitResult' => 2,
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $request = Request::create('http://localhost/en');
        $webspace = new Webspace();
        $webspace->setKey('website');
        $request->attributes->set(SuluRequestAnalyzer::SULU_ATTRIBUTE, new RequestAttributes([
            'webspace' => $webspace,
        ]));
        self::getContainer()->get('request_stack')->push($request);

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array<int, array<string, mixed>> $examples */
        $examples = $result['content']['examples'];
        self::assertCount(2, $examples);
        self::assertSame('Alpha', $examples[0]['title']);
        self::assertSame('Bravo', $examples[1]['title']);

        /** @var array{total: int|null, paginated: bool, hasNextPage: bool|null, maxPage: int|null, limit: int|null, maxPerPage: int|null} $view */
        $view = $result['view']['examples'];
        self::assertFalse($view['paginated']);

        self::assertFalse($view['hasNextPage']);
        self::assertSame(2, $view['total']);
        self::assertNull($view['maxPage']);
        self::assertSame(2, $view['limit']);
        self::assertNull($view['maxPerPage']);
    }

    public function testResolveSmartContentExposesTagNamesInView(): void
    {
        $beach = self::createTag(['name' => 'Beach']);
        $city = self::createTag(['name' => 'City']);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Listing',
                    'url' => '/listing',
                    'examples' => [
                        'tags' => [$beach->getId(), $city->getId()],
                        'tagOperator' => 'OR',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();
        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{tags: array<int, string>} $view */
        $view = $result['view']['examples'];
        self::assertSame(['Beach', 'City'], $view['tags']);
    }

    public function testResolveExampleSmartContentFilterByCategoriesAndTagsWithOperators(): void
    {
        $cat1 = self::createCategory(['key' => 'category-1']);
        $cat2 = self::createCategory(['key' => 'category-2']);
        $tag1 = self::createTag(['name' => 'tag-1']);
        $tag2 = self::createTag(['name' => 'tag-2']);
        static::getEntityManager()->flush();

        // matches: both tags and category1
        $match = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Match',
                    'url' => '/match',
                    'excerptCategories' => [$cat1->getId()],
                    'excerptTags' => [$tag1->getId(), $tag2->getId()],
                ],
            ],
        ], ['create_route' => true]);
        // only one tag -> should not match when tagOperator AND
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Partial',
                    'url' => '/partial',
                    'excerptCategories' => [$cat1->getId()],
                    'excerptTags' => [$tag1->getId()],
                ],
            ],
        ]);
        // different category -> no match
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Other',
                    'url' => '/other',
                    'excerptCategories' => [$cat2->getId()],
                    'excerptTags' => [$tag1->getId(), $tag2->getId()],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Filter Page',
                    'url' => '/filter',
                    'examples' => [
                        'categories' => [$cat1->getId()],
                        'categoryOperator' => 'OR',
                        'tags' => [$tag1->getId(), $tag2->getId()],
                        'tagOperator' => 'AND',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $request = Request::create('http://localhost/en');
        $webspace = new Webspace();
        $webspace->setKey('website');
        $request->attributes->set(SuluRequestAnalyzer::SULU_ATTRIBUTE, new RequestAttributes([
            'webspace' => $webspace,
        ]));
        self::getContainer()->get('request_stack')->push($request);

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array<int, array<string, mixed>> $examples */
        $examples = $result['content']['examples'];
        self::assertCount(1, $examples);
        self::assertSame('Match', $examples[0]['title']);
        self::assertSame('/match', $examples[0]['url']);
    }

    public function testResolveExampleSmartContentPresentAs(): void
    {
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'One',
                    'url' => '/one',
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Two',
                    'url' => '/two',
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Settings Page',
                    'url' => '/settings',
                    'examples' => [
                        'presentAs' => 'grid',
                        'sortBy' => 'title',
                        'sortMethod' => 'DESC',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $request = Request::create('http://localhost/en');
        $webspace = new Webspace();
        $webspace->setKey('website');
        $request->attributes->set(SuluRequestAnalyzer::SULU_ATTRIBUTE, new RequestAttributes([
            'webspace' => $webspace,
        ]));
        self::getContainer()->get('request_stack')->push($request);

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{presentAs: string} $view */
        $view = $result['view']['examples'];
        self::assertSame('grid', $view['presentAs']);

        /** @var array<int, array<string, mixed>> $examples */
        $examples = $result['content']['examples'];
        self::assertGreaterThanOrEqual(2, \count($examples));
        // DESC by title -> Two first
        self::assertSame('Two', $examples[0]['title']);
    }

    public function testRequestDrivenFiltersViaQueryParameters(): void
    {
        $catA = self::createCategory(['key' => 'cat-a']);
        $catB = self::createCategory(['key' => 'cat-b']);
        $tagA = self::createTag(['name' => 'tag-a']);
        $tagB = self::createTag(['name' => 'tag-b']);
        static::getEntityManager()->flush();

        // Matches both website tags and website categories
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Match Both',
                    'url' => '/match-both',
                    'excerptCategories' => [$catA->getId(), $catB->getId()],
                    'excerptTags' => [$tagA->getId(), $tagB->getId()],
                ],
            ],
        ]);
        // Only one of the two tags -> excluded with AND
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Partial Tag',
                    'url' => '/partial-tag',
                    'excerptCategories' => [$catA->getId()],
                    'excerptTags' => [$tagA->getId()],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Req Filters',
                    'url' => '/req-filters',
                    'examples' => [
                        'types' => ['default'],
                        // use default operators: tags AND, categories OR set explicitly
                        'categoryOperator' => 'OR',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        // Provide website filters via query string (pick catB so only the first item matches)
        $this->pushWebsiteRequest([
            'categories' => (string) $catB->getId(),
            'tags' => $tagA->getName() . ',' . $tagB->getName(),
        ]);

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array<int, array<string, mixed>> $items */
        $items = $result['content']['examples'];
        self::assertCount(1, $items);
        self::assertSame('Match Both', $items[0]['title']);

        /** @var array<string, mixed> $view */
        $view = $result['view']['examples'];
        self::assertSame(['default'], $view['types']);
        // websiteCategories from query parameters are strings
        self::assertSame([(string) $catB->getId()], $view['websiteCategories']);
        self::assertSame([$tagA->getName(), $tagB->getName()], $view['websiteTags']);
        self::assertSame('OR', $view['websiteCategoryOperator']);
        self::assertSame('OR', $view['websiteTagOperator']); // default
    }

    public function testPaginationSecondPageWithLimit(): void
    {
        static::createExample(['en' => ['live' => ['template' => 'default', 'title' => 'Three', 'url' => '/three']]]);
        static::createExample(['en' => ['live' => ['template' => 'default', 'title' => 'One', 'url' => '/one']]]);
        static::createExample(['en' => ['live' => ['template' => 'default', 'title' => 'Two', 'url' => '/two']]]);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'example-smart-paginated',
                    'title' => 'Paged',
                    'url' => '/paged',
                    'examples' => [
                        'types' => ['default'],
                        'sortBy' => 'title',
                        'sortMethod' => 'ASC',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        // Request second page
        $this->pushWebsiteRequest(['p' => '2']);

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array<int, array<string, mixed>> $items */
        $items = $result['content']['examples'];
        self::assertCount(1, $items);
        self::assertSame('Three', $items[0]['title']);

        /** @var array<string, mixed> $view */
        $view = $result['view']['examples'];
        self::assertSame(2, $view['page']);
        self::assertTrue($view['paginated']);
        self::assertNull($view['limit']);
        self::assertSame(1, $view['maxPerPage']);
        self::assertTrue($view['hasNextPage']);
        self::assertSame(3, $view['maxPage']);
        self::assertSame(3, $view['total']);
    }

    public function testResolveSmartContentWithoutSortByUsesDefaultOrder(): void
    {
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Charlie',
                    'url' => '/charlie-no-sort',
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Alpha',
                    'url' => '/alpha-no-sort',
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'No Sort Page',
                    'url' => '/no-sort',
                    'examples' => [
                        'types' => ['default'],
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();

        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array<int, array<string, mixed>> $examples */
        $examples = $result['content']['examples'];
        self::assertGreaterThanOrEqual(2, \count($examples));
        $titles = \array_map(static fn (array $item): string => \is_string($item['title']) ? $item['title'] : '', $examples);
        self::assertContains('Charlie', $titles);
        self::assertContains('Alpha', $titles);
    }

    public function testResolveSmartContentWithTemplateParam(): void
    {
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Default Template Item',
                    'url' => '/default-item',
                ],
            ],
        ]);

        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'example-2',
                    'title' => 'Example 2 Template Item',
                    'url' => '/example-2-item',
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        // The smart-content-tpl-param template hardcodes templateKeys="default" in its XML params;
        // only examples using that template should be returned, even without filter types.
        $container = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'smart-content-tpl-param',
                    'title' => 'Smart Content Container',
                    'url' => '/smart-content-container',
                    'examples_by_template' => [],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();

        $dimensionContent = $this->contentAggregator->aggregate($container, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];
        self::assertArrayHasKey('examples_by_template', $content);

        /** @var array<int, array<string, mixed>> $items */
        $items = $content['examples_by_template'];

        self::assertCount(1, $items);
        self::assertSame('Default Template Item', $items[0]['title']);
    }

    public function testRecursionMaxDepthReplacesDeepWithNull(): void
    {
        // Create two pages with smart content that selects itself first via title ASC
        $b = static::createExample(['en' => ['live' => [
            'template' => 'default-example-smart-content',
            'title' => 'B',
            'url' => '/b',
            'examples' => ['types' => ['default-example-smart-content'], 'limitResult' => 1, 'sortBy' => 'title', 'sortMethod' => 'ASC'],
        ]]]);
        $a = static::createExample(['en' => ['live' => [
            'template' => 'default-example-smart-content',
            'title' => 'A',
            'url' => '/a',
            'examples' => ['types' => ['default-example-smart-content'], 'limitResult' => 1, 'sortBy' => 'title', 'sortMethod' => 'ASC'],
        ]]]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();

        $dimensionContent = $this->contentAggregator->aggregate($a, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array<int, mixed> $lvl1 */
        $lvl1 = $result['content']['examples'];
        self::assertNotEmpty($lvl1);
        /** @var array<string, mixed> $aItem */
        $aItem = $lvl1[0];
        self::assertSame('A', $aItem['title']);

        // Attempt to walk nested levels up to depth 5 and verify that at or before depth 5, next level becomes null
        $current = $aItem;
        $depth = 1;
        while ($depth <= 5) {
            $nextList = $current['examples'] ?? null;
            if (!\is_array($nextList) || !isset($nextList[0]) || !\is_array($nextList[0])) {
                $first = \is_array($nextList) ? ($nextList[0] ?? null) : null;
                self::assertNull($first);

                return;
            }
            $current = $nextList[0];
            ++$depth;
        }

        // If we still have an array at this point, the next nesting must be null due to maxDepth
        $finalNext = $current['examples'] ?? null;
        $first = \is_array($finalNext) ? ($finalNext[0] ?? null) : null;
        self::assertNull($first);
    }

    public function testSmartContentViewTagsAreUsableAsUrlFilterParameter(): void
    {
        $beach = self::createTag(['name' => 'Beach']);
        $city = self::createTag(['name' => 'City']);
        static::getEntityManager()->flush();

        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Beach Article',
                    'url' => '/beach-article',
                    'excerptTags' => [$beach->getId()],
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'City Article',
                    'url' => '/city-article',
                    'excerptTags' => [$city->getId()],
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Untagged Article',
                    'url' => '/untagged',
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $listing = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Listing',
                    'url' => '/listing',
                    'examples' => [
                        'tags' => [$beach->getId(), $city->getId()],
                        'tagOperator' => 'OR',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();
        $dimensionContent = $this->contentAggregator->aggregate($listing, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{tags: array<int, string>} $view */
        $view = $result['view']['examples'];
        self::assertSame(['Beach', 'City'], $view['tags']);

        $tagParam = \implode(',', $view['tags']);

        $detailPage = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Detail',
                    'url' => '/detail',
                    'examples' => [
                        'types' => ['default'],
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        self::getContainer()->get('request_stack')->pop();
        $this->pushWebsiteRequest(['tags' => $tagParam]);
        $detailDimension = $this->contentAggregator->aggregate($detailPage, ['locale' => 'en', 'stage' => 'live']);
        $filtered = $this->contentResolver->resolve($detailDimension);

        /** @var array<int, array<string, mixed>> $items */
        $items = $filtered['content']['examples'];
        $titles = \array_map(static fn (array $item) => $item['title'], $items);
        self::assertContains('Beach Article', $titles);
        self::assertContains('City Article', $titles);
        self::assertNotContains('Untagged Article', $titles);
    }

    public function testViewTagsAndTagSelectionContentResolveTogether(): void
    {
        $beach = self::createTag(['name' => 'Beach']);
        $city = self::createTag(['name' => 'City']);
        $mountain = self::createTag(['name' => 'Mountain']);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'smart-content-tag-selection',
                    'title' => 'Combo',
                    'url' => '/combo',
                    'examples' => [
                        'tags' => [$beach->getId(), $city->getId()],
                    ],
                    'featured_tags' => [$mountain->getId(), $beach->getId()],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();
        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        // smart_content view.tags exposes plain name strings (Sulu 2.6 shape)
        /** @var array{tags: array<int, string>} $view */
        $view = $result['view']['examples'];
        self::assertSame(['Beach', 'City'], $view['tags']);

        // tag_selection also resolves to plain name strings
        /** @var array<int, string> $featured */
        $featured = $result['content']['featured_tags'];
        self::assertEqualsCanonicalizing(['Mountain', 'Beach'], \array_values($featured));
    }

    public function testResolveSmartContentExposesCategoryWrappersInView(): void
    {
        $catA = self::createCategory(['key' => 'cat-a']);
        self::createCategoryTranslation($catA, ['title' => 'Category A']);
        $catB = self::createCategory(['key' => 'cat-b']);
        self::createCategoryTranslation($catB, ['title' => 'Category B']);
        static::getEntityManager()->flush();

        $page = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Listing',
                    'url' => '/listing',
                    'examples' => [
                        'categories' => [$catA->getId(), $catB->getId()],
                        'categoryOperator' => 'OR',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();
        $dimensionContent = $this->contentAggregator->aggregate($page, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{categories: array<int, CategoryWrapper>, categoryOperator: string} $view */
        $view = $result['view']['examples'];
        self::assertCount(2, $view['categories']);
        self::assertSame($catA->getId(), $view['categories'][0]->getId());
        self::assertSame('cat-a', $view['categories'][0]->getKey());
        self::assertSame('Category A', $view['categories'][0]->getName());
        self::assertSame($catB->getId(), $view['categories'][1]->getId());
        self::assertSame('OR', $view['categoryOperator']);
    }

    public function testSmartContentViewCategoriesAreUsableAsUrlFilterParameter(): void
    {
        $catA = self::createCategory(['key' => 'cat-a']);
        $catB = self::createCategory(['key' => 'cat-b']);
        static::getEntityManager()->flush();

        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Cat A Article',
                    'url' => '/cat-a-article',
                    'excerptCategories' => [$catA->getId()],
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Cat B Article',
                    'url' => '/cat-b-article',
                    'excerptCategories' => [$catB->getId()],
                ],
            ],
        ]);
        static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Uncategorized Article',
                    'url' => '/uncategorized',
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $listing = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Listing',
                    'url' => '/listing',
                    'examples' => [
                        'categories' => [$catA->getId(), $catB->getId()],
                        'categoryOperator' => 'OR',
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        $this->pushWebsiteRequest();
        $dimensionContent = $this->contentAggregator->aggregate($listing, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var array{categories: array<int, CategoryWrapper>} $view */
        $view = $result['view']['examples'];
        self::assertSame($catA->getId(), $view['categories'][0]->getId());
        self::assertSame($catB->getId(), $view['categories'][1]->getId());

        // mimic the typical template pattern: build ?categories=... from the resolved wrappers
        $categoryParam = \implode(',', \array_map(
            static fn (CategoryWrapper $cat) => (string) $cat->getId(),
            $view['categories'],
        ));

        $detailPage = static::createExample([
            'en' => [
                'live' => [
                    'template' => 'default-example-smart-content',
                    'title' => 'Detail',
                    'url' => '/detail',
                    'examples' => [
                        'types' => ['default'],
                    ],
                ],
            ],
        ]);
        static::getEntityManager()->flush();

        self::getContainer()->get('request_stack')->pop();
        $this->pushWebsiteRequest(['categories' => $categoryParam]);
        $detailDimension = $this->contentAggregator->aggregate($detailPage, ['locale' => 'en', 'stage' => 'live']);
        $filtered = $this->contentResolver->resolve($detailDimension);

        /** @var array<int, array<string, mixed>> $items */
        $items = $filtered['content']['examples'];
        $titles = \array_map(static fn (array $item) => $item['title'], $items);
        self::assertContains('Cat A Article', $titles);
        self::assertContains('Cat B Article', $titles);
        self::assertNotContains('Uncategorized Article', $titles);
    }
}
