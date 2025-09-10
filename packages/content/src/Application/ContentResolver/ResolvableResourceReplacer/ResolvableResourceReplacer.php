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

namespace Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

/**
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ResolvableResourceReplacer implements ResolvableResourceReplacerInterface
{
    public function __construct(
        private ReferenceStoreInterface $referenceStore
    ) {
    }

    public function replaceResolvableResourcesWithResolvedValues(
        array $content,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array {
        if ($depth > $maxDepth) {
            // replace non resolved resources with null
            \array_walk_recursive($content, function(&$value) {
                if ($value instanceof ResolvableResource) {
                    // TODO add callback with exception in dev mode
                    $value = null;
                }
            });

            return $content;
        }

        if (0 === \count($resolvedResources)) {
            return $content;
        }

        $hasReplaced = false;
        \array_walk_recursive($content, function(&$value) use ($resolvedResources, &$hasReplaced) {
            if (
                $value instanceof ResolvableInterface
            ) {
                $resource = $resolvedResources[$value->getResourceLoaderKey()][$value->getId()][$value->getMetadataIdentifier()] ?? null;

                // Populate ReferenceStore if resource was successfully loaded and has resourceKey
                if (null !== $resource && $value instanceof ResolvableResource && $value->getResourceKey()) {
                    $this->populateReferenceStore($value->getId(), $value->getResourceKey());
                }

                $value = $value->executeResourceCallback(
                    $resource,
                );
                $hasReplaced = true;
            }
        });

        // Recursively replace ResolvableResource instances in nested arrays
        // if a replacement was made in the previous step.
        // This is necessary to resolve nested ResolvableResource instances
        // which might have been added during the first replacement,
        // e.g., when a ResolvableResource is replaced with an array containing another ResolvableResource.
        if ($hasReplaced) {
            $content = $this->replaceResolvableResourcesWithResolvedValues(
                $content,
                $resolvedResources,
                $depth + 1,
                $maxDepth
            );
        }

        return $content;
    }

    private function populateReferenceStore(string|int $resourceId, string $resourceKey): void
    {
        $this->referenceStore->add((string) $resourceId, $resourceKey);
    }
}
