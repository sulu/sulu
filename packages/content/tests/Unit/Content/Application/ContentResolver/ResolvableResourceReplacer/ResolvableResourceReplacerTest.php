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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacer;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

class ResolvableResourceReplacerTest extends TestCase
{
    use ProphecyTrait;

    private ResolvableResourceReplacer $replacer;

    private ReferenceStoreInterface $referenceStore;

    protected function setUp(): void
    {
        $this->referenceStore = new ReferenceStore();
        $this->replacer = new ResolvableResourceReplacer($this->referenceStore);
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
                    $metadataId => ['title' => 'Resolved Page Title'],
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('Test', $result['title']);
        self::assertSame('Resolved Page Title', $result['page']);
        self::assertIsArray($result['nested']);
        self::assertArrayHasKey('page', $result['nested']);
        self::assertSame('Resolved Page Title', $result['nested']['page']);

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
                    $firstMetadataId => ['nested_resolvable' => $nestedResource],
                ],
            ],
            'article' => [
                '456' => [
                    $nestedMetadataId => ['title' => 'Article Title'],
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame(['nested_page' => 'Article Title'], $result['page']);

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
        self::assertNull($result['page']);

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
        self::assertSame($resolvableResource, $result['page']);

        // ReferenceStore should be empty when no resources resolved
        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testReplaceWithMissingResource(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource ?? 'fallback';
            },
            null,
            'pages');

        $content = ['page' => $resolvableResource];

        $resolvedResources = [
            'page' => [
                '456' => ['default' => ['title' => 'Other Page']], // Different ID
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        // Should use callback with null resource
        self::assertSame('fallback', $result['page']);

        // ReferenceStore should be empty when resource not found
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
                '123' => [$pageMetadataId => ['title' => 'Page Title', 'content' => 'Page Content']],
            ],
            'article' => [
                '456' => [$articleMetadataId => ['title' => 'Article Title']],
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

        self::assertSame($expected, $result);

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
                '123' => [$metadata1 => ['title' => 'First Page']],
                '456' => [$metadata2 => ['title' => 'Second Page']],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame(['First Page', 'Second Page'], $result['pages']);

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
                    $metadataId => ['title' => 'UUID Page Title'],
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('UUID Page Title', $result['page']);

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
                    $metadataId => ['title' => 'Page Without Key'],
                ],
            ],
        ];

        $result = $this->replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            0,
            5
        );

        self::assertSame('Page Without Key', $result['page']);

        // Verify ReferenceStore is empty when resourceKey is not provided
        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }
}
