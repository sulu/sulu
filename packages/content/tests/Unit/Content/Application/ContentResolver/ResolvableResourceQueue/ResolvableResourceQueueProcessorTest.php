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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\ResolvableResourceQueue;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessor;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

class ResolvableResourceQueueProcessorTest extends TestCase
{
    use ProphecyTrait;

    private ResolvableResourceQueueProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new ResolvableResourceQueueProcessor();
    }

    public function testMergeResolvableResources(): void
    {
        $resource1 = new ResolvableResource('123', 'page', 1, fn ($resource) => $resource);
        $resource2 = new ResolvableResource('456', 'page', 1, fn ($resource) => $resource);
        $resource3 = new ResolvableResource('789', 'article', 2, fn ($resource) => $resource);

        $resolvableResources = [
            1 => [
                'page' => [
                    0 => [
                        '123' => ['default' => $resource1],
                        '456' => ['default' => $resource2],
                    ],
                ],
            ],
        ];

        $existingResolvableResources = [
            2 => [
                'article' => [
                    1 => [
                        '789' => ['default' => $resource3],
                    ],
                ],
            ],
        ];

        $result = $this->processor->mergeResolvableResources($resolvableResources, $existingResolvableResources);

        // Should be sorted by priority descending (krsort)
        self::assertCount(2, $result);
        self::assertArrayHasKey(2, $result);
        self::assertArrayHasKey(1, $result);

        // Check higher priority comes first
        $keys = \array_keys($result);
        self::assertSame(2, $keys[0]);
        self::assertSame(1, $keys[1]);

        // Verify content
        self::assertSame($resource3, $result[2]['article'][1]['789']['default']);
        self::assertSame($resource1, $result[1]['page'][0]['123']['default']);
        self::assertSame($resource2, $result[1]['page'][0]['456']['default']);
    }

    public function testMergeResolvableResourcesOverwrite(): void
    {
        $resource1 = new ResolvableResource('123', 'page', 1, fn ($resource) => 'first');
        $resource2 = new ResolvableResource('123', 'page', 1, fn ($resource) => 'second');

        $resolvableResources = [
            1 => [
                'page' => [
                    0 => [
                        '123' => ['default' => $resource2],
                    ],
                ],
            ],
        ];

        $existingResolvableResources = [
            1 => [
                'page' => [
                    0 => [
                        '123' => ['default' => $resource1],
                    ],
                ],
            ],
        ];

        $result = $this->processor->mergeResolvableResources($resolvableResources, $existingResolvableResources);

        // The newer resource should overwrite the existing one
        self::assertSame($resource2, $result[1]['page'][0]['123']['default']);
    }

    public function testExtractHighestPriorityResources(): void
    {
        $resource1 = new ResolvableResource('123', 'page', 3, fn ($resource) => $resource);
        $resource2 = new ResolvableResource('456', 'page', 2, fn ($resource) => $resource);
        $resource3 = new ResolvableResource('789', 'article', 2, fn ($resource) => $resource);

        $priorityQueue = [
            3 => [
                'page' => [
                    0 => [
                        '123' => ['default' => $resource1],
                    ],
                ],
            ],
            2 => [
                'page' => [
                    1 => [
                        '456' => ['default' => $resource2],
                    ],
                ],
                'article' => [
                    0 => [
                        '789' => ['default' => $resource3],
                    ],
                ],
            ],
        ];

        $result = $this->processor->extractHighestPriorityResources($priorityQueue, 5);

        // Should extract highest priority (3) and remove it from queue
        self::assertCount(1, $priorityQueue);
        self::assertArrayNotHasKey(3, $priorityQueue);
        self::assertArrayHasKey(2, $priorityQueue);

        self::assertArrayHasKey('page', $result['resourcesToLoad']);
        self::assertArrayHasKey('123', $result['resourcesToLoad']['page']);
        self::assertSame($resource1, $result['resourcesToLoad']['page']['123']['default']);

        self::assertSame(0, $result['loaderIdDepths']['page']['123']);
    }

    public function testExtractHighestPriorityResourcesWithMaxDepth(): void
    {
        $resource1 = new ResolvableResource('123', 'page', 1, fn ($resource) => $resource);
        $resource2 = new ResolvableResource('456', 'page', 1, fn ($resource) => $resource);

        $priorityQueue = [
            1 => [
                'page' => [
                    2 => [  // depth 2
                        '123' => ['default' => $resource1],
                    ],
                    5 => [  // depth 5 - should be filtered out
                        '456' => ['default' => $resource2],
                    ],
                ],
            ],
        ];

        $result = $this->processor->extractHighestPriorityResources($priorityQueue, 3);

        // Should only extract resources with depth <= maxDepth
        self::assertArrayHasKey('page', $result['resourcesToLoad']);
        self::assertArrayHasKey('123', $result['resourcesToLoad']['page']);
        self::assertArrayNotHasKey('456', $result['resourcesToLoad']['page']);

        self::assertSame(2, $result['loaderIdDepths']['page']['123']);
    }

    public function testExtractHighestPriorityResourcesEmptyQueue(): void
    {
        $priorityQueue = [];

        $result = $this->processor->extractHighestPriorityResources($priorityQueue, 5);

        self::assertSame(['resourcesToLoad' => [], 'loaderIdDepths' => []], $result);
    }

    public function testMergeResolvableResourcesEmpty(): void
    {
        $result = $this->processor->mergeResolvableResources([], []);

        self::assertSame([], $result);
    }
}
