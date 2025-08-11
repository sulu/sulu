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
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ResolvableResourceQueueProcessor implements ResolvableResourceQueueProcessorInterface
{
    /**
     * Merges the given resolvable resources with the existing resolvable resources.
     * The resolvable resources are ordered by priority and indexed by priority, loader key and object id.
     *
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> $resolvableResources
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> $existingResolvableResources
     *
     * @return array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>
     */
    public function mergeResolvableResources(array $resolvableResources, array $existingResolvableResources): array
    {
        foreach ($resolvableResources as $priority => $loaderResolvableResources) {
            foreach ($loaderResolvableResources as $loaderKey => $resolvableResourcesPerLoader) {
                foreach ($resolvableResourcesPerLoader as $depth => $resolvableResourcePerDepth) {
                    foreach ($resolvableResourcePerDepth as $id => $resolvableResourcePerId) {
                        foreach ($resolvableResourcePerId as $metadataIdentifier => $resolvableResource) {
                            $existingResolvableResources[$priority][$loaderKey][$depth][$id][$metadataIdentifier] = $resolvableResource;
                        }
                    }
                }
            }
        }
        \krsort($existingResolvableResources);

        return $existingResolvableResources;
    }

    public function extractHighestPriorityResources(array &$priorityQueue, int $maxDepth): array
    {
        if (empty($priorityQueue)) {
            return [
                'resourcesToLoad' => [],
                'loaderIdDepths' => [],
            ];
        }

        // Get the highest priority resources (first key due to krsort in mergeResolvableResources)
        $highestPriorityKey = \array_key_first($priorityQueue);
        $resourcesAtPriority = $priorityQueue[$highestPriorityKey];
        unset($priorityQueue[$highestPriorityKey]);

        $loaderIdDepths = [];
        $resourcesToLoad = [];

        // Filter resources with depth too high
        foreach ($resourcesAtPriority as $loaderKey => $resourcePerDepth) {
            foreach ($resourcePerDepth as $depth => $resources) {
                if ($depth > $maxDepth) {
                    continue;
                }
                foreach ($resources as $id => $resource) {
                    $resourcesToLoad[$loaderKey][$id] = $resource;
                    $loaderIdDepths[$loaderKey][$id] = $depth;
                }
            }
        }

        return [
            'resourcesToLoad' => $resourcesToLoad,
            'loaderIdDepths' => $loaderIdDepths,
        ];
    }
}
