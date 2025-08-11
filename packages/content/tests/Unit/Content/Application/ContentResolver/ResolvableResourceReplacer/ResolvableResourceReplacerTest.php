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
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacer;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

class ResolvableResourceReplacerTest extends TestCase
{
    use ProphecyTrait;

    private ResolvableResourceReplacer $replacer;

    protected function setUp(): void
    {
        $this->replacer = new ResolvableResourceReplacer();
    }

    public function testReplaceResolvableResourcesWithResolvedValues(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            }
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
    }

    public function testReplaceWithNestedResolvableResources(): void
    {
        $firstResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return ['nested_page' => $resource['nested_resolvable']];
            }
        );

        $nestedResource = new ResolvableResource(
            '456',
            'article',
            1,
            function(array $resource) {
                return $resource['title'] ?? 'Default Title';
            }
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
    }

    public function testReplaceWithMaxDepthExceeded(): void
    {
        $replacer = new ResolvableResourceReplacer();

        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource;
            });

        $content = ['page' => $resolvableResource];
        $resolvedResources = [];

        $result = $replacer->replaceResolvableResourcesWithResolvedValues(
            $content,
            $resolvedResources,
            3,
            2
        );

        // Should replace with null when max depth exceeded
        self::assertNull($result['page']);
    }

    public function testReplaceWithEmptyResolvedResources(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource;
            }
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
    }

    public function testReplaceWithMissingResource(): void
    {
        $resolvableResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource ?? 'fallback';
            });

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
    }

    public function testReplaceWithComplexNestedStructure(): void
    {
        $pageResource = new ResolvableResource(
            '123',
            'page',
            1,
            function(mixed $resource) {
                return $resource;
            });

        $articleResource = new ResolvableResource(
            '456',
            'article',
            1,
            function(array $resource) {
                return $resource['title'];
            });

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
    }

    public function testReplaceWithArrayOfResolvableResources(): void
    {
        $resource1 = new ResolvableResource(
            '123',
            'page',
            1,
            function(array $resource) {
                return $resource['title'];
            });

        $resource2 = new ResolvableResource(
            '456',
            'page',
            1,
            function(array $resource) {
                return $resource['title'];
            });

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
    }
}
