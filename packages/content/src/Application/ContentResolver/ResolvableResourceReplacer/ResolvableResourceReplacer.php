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

    /**
     * @param array<int|string, mixed> $content
     * @param array<string, array<string|int, array<string, mixed>>> $resolvedResources
     *
     * @return array<int|string, mixed>
     */
    public function replaceResolvableResourcesWithResolvedValues(
        array $content,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array {
        if ($depth > $maxDepth) {
            return $this->replaceUnresolvedWithNull($content);
        }

        if (0 === \count($resolvedResources)) {
            return $content;
        }

        $onlyResolvableResources = true;
        foreach ($content as $key => $value) {
            if ($value instanceof ResolvableInterface) {
                $content[$key] = $this->resolveValue(
                    $value,
                    $resolvedResources
                );

                if (\is_array($content[$key])) {
                    $content[$key] = $this->replaceResolvableResourcesWithResolvedValues(
                        $content[$key],
                        $resolvedResources,
                        $depth + 1,
                        $maxDepth
                    );
                }
                continue;
            }
            $onlyResolvableResources = false;

            if (\is_array($value)) {
                $content[$key] = $this->replaceResolvableResourcesWithResolvedValues(
                    $value,
                    $resolvedResources,
                    $depth, // only increase depth for ResolvableInterface
                    $maxDepth
                );
            }
        }

        // this is required for e.g. selections where some entities are not resolved
        // due to insufficient permissions
        if ($onlyResolvableResources) {
            // remove non-resolved resources if all values were resolvable resources
            $isList = \array_is_list($content);
            $content = \array_filter($content, static fn ($value) => null !== $value);
            if ($isList) {
                $content = \array_values($content);
            }
        }

        return $content;
    }

    /**
     * @param array<string, array<string|int, array<string, mixed>>> $resolvedResources
     */
    private function resolveValue(
        ResolvableInterface $value,
        array $resolvedResources
    ): mixed {
        $loaderKey = $value->getResourceLoaderKey();
        $id = $value->getId();
        $metadataIdentifier = $value->getMetadataIdentifier();

        $resource = $resolvedResources[$loaderKey][$id][$metadataIdentifier] ?? null;

        if (null !== $resource && $value instanceof ResolvableResource && $value->getResourceKey()) {
            $this->populateReferenceStore($value->getId(), $value->getResourceKey());
        }

        return null === $resource ? null : $value->executeResourceCallback($resource);
    }

    /**
     * @param array<int|string, mixed> $content
     *
     * @return array<int|string, mixed>
     */
    private function replaceUnresolvedWithNull(array $content): array
    {
        foreach ($content as $key => $value) {
            if ($value instanceof ResolvableResource) {
                $content[$key] = null;
            } elseif (\is_array($value)) {
                $content[$key] = $this->replaceUnresolvedWithNull($value);
            }
        }

        return $content;
    }

    private function populateReferenceStore(string|int $resourceId, string $resourceKey): void
    {
        $this->referenceStore->add((string) $resourceId, $resourceKey);
    }
}
