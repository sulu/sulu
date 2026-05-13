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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\ResolvableResourceReplacer;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacer;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

class ResolvableResourceReplacerTest extends TestCase
{
    private ResolvableResourceReplacer $replacer;

    private ReferenceStoreInterface $referenceStore;

    protected function setUp(): void
    {
        $this->referenceStore = new ReferenceStore();
        $this->replacer = new ResolvableResourceReplacer($this->referenceStore);
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $view
     *
     * @return array{resolved: mixed, contentViewEnhancement: ContentView}
     */
    private function createResolvedEntry(mixed $resolved, array $view = [], array $content = []): array
    {
        return [
            'resolved' => $resolved,
            'contentViewEnhancement' => ContentView::create($content, $view),
        ];
    }

    public function testReplaceResolvableResourcesWithResolvedValues(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            },
            null,
            'pages'
        );

        $content = [
            'title' => 'Test',
            'page' => $resolvableResource,
            'nested' => [
                'page' => $resolvableResource,
            ],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();
        $resolvedResources = [
            'page' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(['title' => 'Resolved Page Title']),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('Test', $result['content']['title']);
        self::assertSame('Resolved Page Title', $result['content']['page']);
        self::assertIsArray($result['content']['nested']);
        self::assertArrayHasKey('page', $result['content']['nested']);
        self::assertSame('Resolved Page Title', $result['content']['nested']['page']);
        $tags = $this->referenceStore->getAll();
        self::assertContains('pages-123', $tags);
        self::assertCount(1, $tags);
    }

    public function testReplaceWithNestedResolvableResources(): void
    {
        $firstResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return ['nested_page' => $resource['nested_resolvable']];
            },
            null,
            'pages'
        );

        $nestedResource = new ResolvableResource(
            '456',
            'article',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            },
            null,
            'articles'
        );

        $content = [
            'page' => $firstResource,
        ];

        $firstMetadataId = $firstResource->getMetadataIdentifier();
        $nestedMetadataId = $nestedResource->getMetadataIdentifier();

        $resolvedResources = [
            'page' => [
                '123' => [
                    $firstMetadataId => $this->createResolvedEntry(['nested_resolvable' => $nestedResource]),
                ],
            ],
            'article' => [
                '456' => [
                    $nestedMetadataId => $this->createResolvedEntry(['title' => 'Article Title']),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame(['nested_page' => 'Article Title'], $result['content']['page']);

        $tags = $this->referenceStore->getAll();
        self::assertContains('pages-123', $tags);
        self::assertContains('articles-456', $tags);
        self::assertCount(2, $tags);
    }

    public function testReplaceWithMaxDepthExceeded(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource;
            },
            null,
            'pages');

        $content = ['page' => $resolvableResource];
        $resolvedResources = [];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            3,
            2
        );

        self::assertNull($result['content']['page']);

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testReplaceWithEmptyResolvedResources(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource;
            },
            null,
            'pages'
        );

        $content = ['page' => $resolvableResource];
        $resolvedResources = [];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame($resolvableResource, $result['content']['page']);

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testReplaceWithComplexNestedStructure(): void
    {
        $pageResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource;
            },
            null,
            'pages');

        $articleResource = new ResolvableResource(
            '456',
            'article',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'articles');

        $content = [
            'items' => [
                ['type' => 'page', 'resource' => $pageResource],
                ['type' => 'article', 'resource' => $articleResource],
            ],
            'featured' => $pageResource,
        ];

        $pageMetadataId = $pageResource->getMetadataIdentifier();
        $articleMetadataId = $articleResource->getMetadataIdentifier();

        $resolvedResources = [
            'page' => [
                '123' => [$pageMetadataId => $this->createResolvedEntry(['title' => 'Page Title', 'content' => 'Page Content'])],
            ],
            'article' => [
                '456' => [$articleMetadataId => $this->createResolvedEntry(['title' => 'Article Title'])],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        $expected = [
            'items' => [
                ['type' => 'page', 'resource' => ['title' => 'Page Title', 'content' => 'Page Content']],
                ['type' => 'article', 'resource' => 'Article Title'],
            ],
            'featured' => ['title' => 'Page Title', 'content' => 'Page Content'],
        ];

        self::assertSame($expected, $result['content']);

        $tags = $this->referenceStore->getAll();
        self::assertContains('pages-123', $tags);
        self::assertContains('articles-456', $tags);
        self::assertCount(2, $tags);
    }

    public function testReplaceWithArrayOfResolvableResources(): void
    {
        $resource1 = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'pages');

        $resource2 = new ResolvableResource(
            '456',
            'page',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'pages');

        $content = [
            'pages' => [$resource1, $resource2],
        ];

        $metadata1 = $resource1->getMetadataIdentifier();
        $metadata2 = $resource2->getMetadataIdentifier();

        $resolvedResources = [
            'page' => [
                '123' => [$metadata1 => $this->createResolvedEntry(['title' => 'First Page'])],
                '456' => [$metadata2 => $this->createResolvedEntry(['title' => 'Second Page'])],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame(['First Page', 'Second Page'], $result['content']['pages']);

        $tags = $this->referenceStore->getAll();
        self::assertContains('pages-123', $tags);
        self::assertContains('pages-456', $tags);
        self::assertCount(2, $tags);
    }

    public function testReferenceStoreWithUuidResources(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $resolvableResource = new ResolvableResource(
            $uuid,
            'page',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            },
            null,
            'pages'
        );

        $content = ['page' => $resolvableResource];

        $metadataId = $resolvableResource->getMetadataIdentifier();
        $resolvedResources = [
            'page' => [
                $uuid => [
                    $metadataId => $this->createResolvedEntry(['title' => 'UUID Page Title']),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('UUID Page Title', $result['content']['page']);

        $tags = $this->referenceStore->getAll();
        self::assertContains($uuid, $tags);
        self::assertCount(1, $tags);
    }

    public function testReferenceStoreNotPopulatedWithoutResourceKey(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            }
        );

        $content = ['page' => $resolvableResource];

        $metadataId = $resolvableResource->getMetadataIdentifier();
        $resolvedResources = [
            'page' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(['title' => 'Page Without Key']),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('Page Without Key', $result['content']['page']);

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testViewDataFromResolvedResources(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $content = [
            'mySnippets' => [$resolvableResource],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(
                        ['title' => 'Snippet Title'],
                        ['id' => '123', 'template' => 'test-template'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame(['Snippet Title'], $result['content']['mySnippets']);
        self::assertArrayHasKey('[mySnippets]', $result['viewEnhancements']);
        self::assertSame('items', $result['viewEnhancements']['[mySnippets]']['itemsPropertyName']);
        self::assertCount(1, $result['viewEnhancements']['[mySnippets]']['items']);
        self::assertSame(
            ['id' => '123', 'template' => 'test-template'],
            $result['viewEnhancements']['[mySnippets]']['items'][0]
        );
    }

    public function testViewDataWithMultipleResources(): void
    {
        $resource1 = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $resource2 = new ResolvableResource(
            '456',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $content = [
            'snippets' => [$resource1, $resource2],
        ];

        $metadata1 = $resource1->getMetadataIdentifier();
        $metadata2 = $resource2->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadata1 => $this->createResolvedEntry(
                        ['title' => 'First Snippet'],
                        ['id' => '123', 'template' => 'template-a'],
                    ),
                ],
                '456' => [
                    $metadata2 => $this->createResolvedEntry(
                        ['title' => 'Second Snippet'],
                        ['id' => '456', 'template' => 'template-b'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame(['First Snippet', 'Second Snippet'], $result['content']['snippets']);
        self::assertArrayHasKey('[snippets]', $result['viewEnhancements']);
        self::assertSame('items', $result['viewEnhancements']['[snippets]']['itemsPropertyName']);
        self::assertCount(2, $result['viewEnhancements']['[snippets]']['items']);
        self::assertSame(['id' => '123', 'template' => 'template-a'], $result['viewEnhancements']['[snippets]']['items'][0]);
        self::assertSame(['id' => '456', 'template' => 'template-b'], $result['viewEnhancements']['[snippets]']['items'][1]);
    }

    public function testViewDataEmptyWhenResourceNotResolved(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $content = [
            'mySnippets' => [$resolvableResource],
        ];

        $resolvedResources = [];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        $mySnippets = $result['content']['mySnippets'];
        self::assertIsArray($mySnippets);
        self::assertSame($resolvableResource, $mySnippets[0]);
        self::assertEmpty($result['viewEnhancements']);
    }

    public function testViewDataWithNestedPath(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $content = [
            'template' => [
                'snippets' => [$resolvableResource],
            ],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(
                        ['title' => 'Snippet Title'],
                        ['id' => '123', 'template' => 'default'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        $templateContent = $result['content']['template'];
        self::assertIsArray($templateContent);
        self::assertSame(['Snippet Title'], $templateContent['snippets']);
        self::assertArrayHasKey('[template][snippets]', $result['viewEnhancements']);
        self::assertSame('items', $result['viewEnhancements']['[template][snippets]']['itemsPropertyName']);
        self::assertCount(1, $result['viewEnhancements']['[template][snippets]']['items']);
        self::assertSame(
            ['id' => '123', 'template' => 'default'],
            $result['viewEnhancements']['[template][snippets]']['items'][0]
        );
    }

    public function testViewDataWithIndexedNestedPath(): void
    {
        $resource1 = new ResolvableResource(
            '123',
            'snippet',
            1,
            static function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $resource2 = new ResolvableResource(
            '456',
            'snippet',
            1,
            static function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            'items',
        );

        $content = [
            'blocks' => [
                ['snippets' => [$resource1]],
                ['snippets' => [$resource2]],
            ],
        ];

        $metadata1 = $resource1->getMetadataIdentifier();
        $metadata2 = $resource2->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadata1 => $this->createResolvedEntry(
                        ['title' => 'Snippet 1'],
                        ['uuid' => 'u-123'],
                    ),
                ],
                '456' => [
                    $metadata2 => $this->createResolvedEntry(
                        ['title' => 'Snippet 2'],
                        ['uuid' => 'u-456'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertArrayHasKey('[blocks][0][snippets]', $result['viewEnhancements']);
        self::assertArrayHasKey('[blocks][1][snippets]', $result['viewEnhancements']);
        self::assertArrayNotHasKey('[blocks][snippets]', $result['viewEnhancements']);

        self::assertSame('items', $result['viewEnhancements']['[blocks][0][snippets]']['itemsPropertyName']);
        self::assertSame(
            ['uuid' => 'u-123'],
            $result['viewEnhancements']['[blocks][0][snippets]']['items'][0]
        );

        self::assertSame('items', $result['viewEnhancements']['[blocks][1][snippets]']['itemsPropertyName']);
        self::assertSame(
            ['uuid' => 'u-456'],
            $result['viewEnhancements']['[blocks][1][snippets]']['items'][0]
        );
    }

    public function testPreComputedViewDataAccumulated(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return [
                    'content' => ['title' => $resource['rawTitle'], 'text' => $resource['rawText']],
                    'view' => ['template' => $resource['template']],
                    'id' => '123',
                ];
            },
            null,
            'snippets',
            'items',
        );

        $content = [
            'mySnippets' => [$resolvableResource],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(
                        [
                            'rawTitle' => 'My Title',
                            'rawText' => 'Some text',
                            'template' => 'custom-template',
                        ],
                        ['id' => '123', 'template' => 'custom-template'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertArrayHasKey('[mySnippets]', $result['viewEnhancements']);
        self::assertSame('items', $result['viewEnhancements']['[mySnippets]']['itemsPropertyName']);
        self::assertSame(
            ['id' => '123', 'template' => 'custom-template'],
            $result['viewEnhancements']['[mySnippets]']['items'][0]
        );
    }

    public function testViewDataWithNullItemsPropertyNameFlatMerge(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
        );

        $content = [
            'mySnippet' => $resolvableResource,
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(
                        ['title' => 'Snippet Title'],
                        ['id' => '123', 'template' => 'default'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('Snippet Title', $result['content']['mySnippet']);
        self::assertArrayHasKey('[mySnippet]', $result['viewEnhancements']);
        self::assertNull($result['viewEnhancements']['[mySnippet]']['itemsPropertyName']);
        self::assertCount(1, $result['viewEnhancements']['[mySnippet]']['items']);
        self::assertSame(
            ['id' => '123', 'template' => 'default'],
            $result['viewEnhancements']['[mySnippet]']['items'][0]
        );
    }

    public function testContentDataMergedIntoResolvedValue(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            null,
            null,
            'pages'
        );

        $content = [
            'page' => $resolvableResource,
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();
        $resolvedResources = [
            'page' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(
                        ['title' => 'Page Title', 'url' => '/page'],
                        [],
                        ['authored' => '2024-01-01T00:00:00+00:00', 'lastModified' => '2024-06-15T12:00:00+00:00'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertIsArray($result['content']['page']);
        self::assertSame('Page Title', $result['content']['page']['title']);
        self::assertSame('/page', $result['content']['page']['url']);
        self::assertSame('2024-01-01T00:00:00+00:00', $result['content']['page']['authored']);
        self::assertSame('2024-06-15T12:00:00+00:00', $result['content']['page']['lastModified']);
    }

    public function testContentDataNotMergedWhenResolvedIsNotArray(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'pages'
        );

        $content = [
            'page' => $resolvableResource,
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();
        $resolvedResources = [
            'page' => [
                '123' => [
                    $metadataId => $this->createResolvedEntry(
                        ['title' => 'Page Title'],
                        [],
                        ['authored' => '2024-01-01T00:00:00+00:00'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('Page Title', $result['content']['page']);
    }

    public function testContentDataMergedIntoEachItemInMultiSelection(): void
    {
        $resource1 = new ResolvableResource(
            '123',
            'page',
            1,
            null,
            null,
            'pages'
        );

        $resource2 = new ResolvableResource(
            '456',
            'page',
            1,
            null,
            null,
            'pages'
        );

        $content = [
            'pages' => [$resource1, $resource2],
        ];

        $metadata1 = $resource1->getMetadataIdentifier();
        $metadata2 = $resource2->getMetadataIdentifier();

        $resolvedResources = [
            'page' => [
                '123' => [
                    $metadata1 => $this->createResolvedEntry(
                        ['title' => 'First Page'],
                        [],
                        ['authored' => '2024-01-01T00:00:00+00:00', 'lastModified' => '2024-03-01T00:00:00+00:00'],
                    ),
                ],
                '456' => [
                    $metadata2 => $this->createResolvedEntry(
                        ['title' => 'Second Page'],
                        [],
                        ['authored' => '2024-06-01T00:00:00+00:00', 'lastModified' => '2024-09-01T00:00:00+00:00'],
                    ),
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        $pages = $result['content']['pages'];
        self::assertIsArray($pages);
        self::assertCount(2, $pages);

        self::assertIsArray($pages[0]);
        self::assertSame('First Page', $pages[0]['title']);
        self::assertSame('2024-01-01T00:00:00+00:00', $pages[0]['authored']);
        self::assertSame('2024-03-01T00:00:00+00:00', $pages[0]['lastModified']);

        self::assertIsArray($pages[1]);
        self::assertSame('Second Page', $pages[1]['title']);
        self::assertSame('2024-06-01T00:00:00+00:00', $pages[1]['authored']);
        self::assertSame('2024-09-01T00:00:00+00:00', $pages[1]['lastModified']);
    }

    public function testReplaceResolvableResourcesInViewReplacesNestedResolvables(): void
    {
        $tagA = new ResolvableResource(3, 'tag', 0, null, null, 'tags');
        $tagB = new ResolvableResource(4, 'tag', 0, null, null, 'tags');

        $view = [
            'tags' => [$tagA, $tagB],
            'plain' => 'untouched',
            'nested' => [
                'tag' => $tagA,
            ],
        ];

        $resolvedResources = [
            'tag' => [
                3 => [$tagA->getMetadataIdentifier() => $this->createResolvedEntry('Beach')],
                4 => [$tagB->getMetadataIdentifier() => $this->createResolvedEntry('City')],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesInView($view, $resolvedResources);

        self::assertSame(['Beach', 'City'], $result['tags']);
        self::assertSame('untouched', $result['plain']);
        self::assertSame('Beach', $result['nested']['tag']);

        $refs = $this->referenceStore->getAll();
        self::assertContains('tags-3', $refs);
        self::assertContains('tags-4', $refs);
    }

    public function testReplaceResolvableResourcesInViewReturnsEarlyWithNoResolvedResources(): void
    {
        $tag = new ResolvableResource(3, 'tag', 0);
        $view = ['tags' => [$tag]];

        $result = $this->replacer->replaceResolvableResourcesInView($view, []);

        self::assertSame($view, $result);
    }
}
