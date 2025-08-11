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

namespace Sulu\Content\Application\ContentResolver\ResolvableResourceQueue;

use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;

/**
 * @internal This interface is intended for internal use only within the package/library.
 * Modifying or depending on this interface may result in unexpected behavior and is not supported.
 */
interface ResolvableResourceQueueProcessorInterface
{
    /**
     * Merges the given resolvable resources with the existing resolvable resources.
     * The resolvable resources are ordered by priority and indexed by priority, loader key, object id and metadataIdentifier.
     *
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> $resolvableResources
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> $existingResolvableResources
     *
     * @return array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>
     */
    public function mergeResolvableResources(array $resolvableResources, array $existingResolvableResources): array;

    /**
     * Gets the highest priority resources from the queue.
     * Note: This modifies the provided priority queue by removing the processed items.
     *
     * @param array<int, array<string, array<int, array<int|string, array<string, ResolvableInterface>>>>> &$priorityQueue Reference to the priority queue
     * @param int $maxDepth Maximum depth for resource resolution
     *
     * @return array{
     *     resourcesToLoad: array<string, array<int|string, array<string, ResolvableInterface>>>,
     *     loaderIdDepths: array<string, array<int|string, int>>
     * }
     */
    public function extractHighestPriorityResources(array &$priorityQueue, int $maxDepth): array;
}
