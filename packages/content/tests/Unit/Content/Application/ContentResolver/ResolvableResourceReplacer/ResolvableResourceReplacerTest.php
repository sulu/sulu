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
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

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
     * @return array{source: mixed, resolved: mixed}
     */
    private function createResolvedEntry(mixed $resolved, mixed $source = null): array
    {
        return [
            'source' => $source ?? $resolved,
            'resolved' => $resolved,
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

        // Use the actual metadata identifier from the resource
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
        // Verify ReferenceStore was populated
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

        // Verify ReferenceStore was populated for both resources
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

        // Should replace with null when max depth exceeded
        self::assertNull($result['content']['page']);

        // ReferenceStore should be empty when max depth exceeded
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

        // Should remain unchanged when no resolved resources
        self::assertSame($resolvableResource, $result['content']['page']);

        // ReferenceStore should be empty when no resources resolved
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

        // Verify ReferenceStore was populated for both resources
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

        // Verify ReferenceStore was populated for both pages
        $tags = $this->referenceStore->getAll();
        self::assertContains('pages-123', $tags);
        self::assertContains('pages-456', $tags);
        self::assertCount(2, $tags);
    }

    public function testReferenceStoreWithUuidResources(): void
    {
        // Create a ResolvableResource with a UUID as ID
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

        // Verify ReferenceStore stores UUID directly without prefix
        $tags = $this->referenceStore->getAll();
        self::assertContains($uuid, $tags);
        self::assertCount(1, $tags);
    }

    public function testReferenceStoreNotPopulatedWithoutResourceKey(): void
    {
        // Create a ResolvableResource without resourceKey
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            }
            // No metadata and no resourceKey provided
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

        // Verify ReferenceStore is empty when resourceKey is not provided
        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testViewCallbackExecutedWithSourceEntity(): void
    {
        $callbackCalled = false;
        $receivedSource = null;

        $example = new Example();
        $example->id = '123';
        $sourceEntity = new ExampleDimensionContent($example);
        $sourceEntity->setTemplateKey('test-template');

        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            function(ExampleDimensionContent $source) use (&$callbackCalled, &$receivedSource) {
                $callbackCalled = true;
                $receivedSource = $source;

                return [
                    'id' => $source->getResource()->getId(),
                    'template' => $source->getTemplateKey(),
                ];
            }
        );

        $content = [
            'mySnippets' => [$resolvableResource],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadataId => [
                        'source' => $sourceEntity,
                        'resolved' => ['title' => 'Snippet Title'],
                    ],
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertTrue($callbackCalled, 'viewCallback should be called');
        self::assertSame($sourceEntity, $receivedSource);
        self::assertSame(['Snippet Title'], $result['content']['mySnippets']);
        self::assertArrayHasKey('[mySnippets]', $result['viewEnhancements']);
        self::assertTrue($result['viewEnhancements']['[mySnippets]']['isList']);
        self::assertCount(1, $result['viewEnhancements']['[mySnippets]']['items']);
        self::assertSame(
            ['id' => '123', 'template' => 'test-template'],
            $result['viewEnhancements']['[mySnippets]']['items'][0]
        );
    }

    public function testViewCallbackWithMultipleResources(): void
    {
        $example1 = new Example();
        $example1->id = '123';
        $source1 = new ExampleDimensionContent($example1);
        $source1->setTemplateKey('template-a');

        $example2 = new Example();
        $example2->id = '456';
        $source2 = new ExampleDimensionContent($example2);
        $source2->setTemplateKey('template-b');

        $viewCallback = static function(ExampleDimensionContent $source): array {
            return [
                'id' => $source->getResource()->getId(),
                'template' => $source->getTemplateKey(),
            ];
        };

        $resource1 = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            $viewCallback
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
            $viewCallback
        );

        $content = [
            'snippets' => [$resource1, $resource2],
        ];

        $metadata1 = $resource1->getMetadataIdentifier();
        $metadata2 = $resource2->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [$metadata1 => ['source' => $source1, 'resolved' => ['title' => 'First Snippet']]],
                '456' => [$metadata2 => ['source' => $source2, 'resolved' => ['title' => 'Second Snippet']]],
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
        self::assertTrue($result['viewEnhancements']['[snippets]']['isList']);
        self::assertCount(2, $result['viewEnhancements']['[snippets]']['items']);
        self::assertSame(['id' => '123', 'template' => 'template-a'], $result['viewEnhancements']['[snippets]']['items'][0]);
        self::assertSame(['id' => '456', 'template' => 'template-b'], $result['viewEnhancements']['[snippets]']['items'][1]);
    }

    public function testViewCallbackNotCalledWhenSourceIsNull(): void
    {
        $callbackCalled = false;

        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            function(ExampleDimensionContent $source) use (&$callbackCalled) {
                $callbackCalled = true;

                return ['id' => $source->getResource()->getId()];
            }
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

        self::assertFalse($callbackCalled);
        $mySnippets = $result['content']['mySnippets'];
        self::assertIsArray($mySnippets);
        self::assertSame($resolvableResource, $mySnippets[0]);
        self::assertEmpty($result['viewEnhancements']);
    }

    public function testViewCallbackWithNestedPath(): void
    {
        $example = new Example();
        $example->id = '123';
        $source = new ExampleDimensionContent($example);
        $source->setTemplateKey('default');

        $resolvableResource = new ResolvableResource(
            '123',
            'snippet',
            1,
            function(array $resource) {
                return $resource['title'];
            },
            null,
            'snippets',
            function(ExampleDimensionContent $source) {
                return [
                    'id' => $source->getResource()->getId(),
                    'template' => $source->getTemplateKey(),
                ];
            }
        );

        $content = [
            'template' => [
                'snippets' => [$resolvableResource],
            ],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [$metadataId => ['source' => $source, 'resolved' => ['title' => 'Snippet Title']]],
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
        self::assertTrue($result['viewEnhancements']['[template][snippets]']['isList']);
        self::assertCount(1, $result['viewEnhancements']['[template][snippets]']['items']);
        self::assertSame(
            ['id' => '123', 'template' => 'default'],
            $result['viewEnhancements']['[template][snippets]']['items'][0]
        );
    }

    public function testViewCallbackWithIndexedNestedPath(): void
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
            static function(array $source): array {
                return ['uuid' => $source['uuid']];
            }
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
            static function(array $source): array {
                return ['uuid' => $source['uuid']];
            }
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
                    $metadata1 => ['source' => ['uuid' => 'u-123'], 'resolved' => ['title' => 'Snippet 1']],
                ],
                '456' => [
                    $metadata2 => ['source' => ['uuid' => 'u-456'], 'resolved' => ['title' => 'Snippet 2']],
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

        self::assertTrue($result['viewEnhancements']['[blocks][0][snippets]']['isList']);
        self::assertSame(
            ['uuid' => 'u-123'],
            $result['viewEnhancements']['[blocks][0][snippets]']['items'][0]
        );

        self::assertTrue($result['viewEnhancements']['[blocks][1][snippets]']['isList']);
        self::assertSame(
            ['uuid' => 'u-456'],
            $result['viewEnhancements']['[blocks][1][snippets]']['items'][0]
        );
    }

    public function testViewCallbackReceivesSourceEntityNotResolvedValue(): void
    {
        $receivedSource = null;

        $example = new Example();
        $example->id = '123';
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setTemplateKey('custom-template');

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
            function(ExampleDimensionContent $source) use (&$receivedSource) {
                $receivedSource = $source;

                return [
                    'id' => $source->getResource()->getId(),
                    'template' => $source->getTemplateKey(),
                ];
            }
        );

        $content = [
            'mySnippets' => [$resolvableResource],
        ];

        $metadataId = $resolvableResource->getMetadataIdentifier();

        $resolvedResources = [
            'snippet' => [
                '123' => [
                    $metadataId => [
                        'source' => $dimensionContent,
                        'resolved' => [
                            'rawTitle' => 'My Title',
                            'rawText' => 'Some text',
                            'template' => 'custom-template',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame($dimensionContent, $receivedSource);
        self::assertSame('123', $receivedSource->getResource()->getId());
        self::assertSame('custom-template', $receivedSource->getTemplateKey());

        self::assertArrayHasKey('[mySnippets]', $result['viewEnhancements']);
        self::assertTrue($result['viewEnhancements']['[mySnippets]']['isList']);
        self::assertSame(
            ['id' => '123', 'template' => 'custom-template'],
            $result['viewEnhancements']['[mySnippets]']['items'][0]
        );
    }
}
