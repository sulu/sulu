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

namespace Sulu\Content\Application\ContentResolver\ResolvableResourceLoader;

use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Content\Application\SmartResolver\SmartResolverProviderInterface;

/**
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ResolvableResourceLoader implements ResolvableResourceLoaderInterface
{
    public function __construct(
        private ResourceLoaderProvider $resourceLoaderProvider,
        private SmartResolverProviderInterface $smartResolverProvider
    ) {
    }

    public function loadResources(array $resourcesPerLoader, ?string $locale): array
    {
        $loadedResources = [];
        foreach ($resourcesPerLoader as $loaderKey => $resourcesToLoad) {
            if (!$loaderKey) {
                throw new \RuntimeException(\sprintf('ResourceLoader key "%s" is invalid', $loaderKey));
            }

            $smartResolvableResources = [];
            $resolvableResources = [];
            $metadataIdentifiersPerResourceId = [];
            foreach ($resourcesToLoad as $id => $resourcePerMetadataIdentifier) {
                foreach ($resourcePerMetadataIdentifier as $metadataIdentifier => $resource) {
                    $metadataIdentifiersPerResourceId[$id][] = $metadataIdentifier;
                    if ($resource instanceof SmartResolvable) {
                        $smartResolvableResources[$id] = $resource;
                    } elseif ($resource instanceof ResolvableResource) {
                        $resolvableResources[$id] = $resource;
                    } else {
                        throw new \RuntimeException(\sprintf('Resource with id "%s" is neither a SmartResolvable nor a ResolvableResource', $id));
                    }
                }
            }

            if (\count($smartResolvableResources) > 0) {
                $result = $this->loadSmartResolvableResources($smartResolvableResources, $locale);
                foreach ($result as $id => $loadedResource) {
                    foreach ($metadataIdentifiersPerResourceId[$id] as $metadataIdentifier) {
                        $loadedResources[$loaderKey][$id][$metadataIdentifier] = $loadedResource;
                    }
                }
            }

            if (\count($resolvableResources) > 0) {
                $result = $this->loadResolvableResources($resolvableResources, $loaderKey, $locale);
                foreach ($result as $id => $loadedResource) {
                    foreach ($metadataIdentifiersPerResourceId[$id] as $metadataIdentifier) {
                        $loadedResources[$loaderKey][$id][$metadataIdentifier] = $loadedResource;
                    }
                }
            }
        }

        return $loadedResources;
    }

    /**
     * @param array<SmartResolvable> $smartResources
     *
     * @return array<mixed>
     */
    private function loadSmartResolvableResources(array $smartResources, ?string $locale): array
    {
        $loadedResources = [];

        foreach ($smartResources as $id => $smartResource) {
            $resourceLoaderKey = $smartResource->getResourceLoaderKey();
            $smartResolver = $this->smartResolverProvider->getSmartResolver($resourceLoaderKey);

            $loadedResources[$id] = $smartResolver->resolve(
                $smartResource,
                $locale,
            );
        }

        return $loadedResources;
    }

    /**
     * @param array<ResolvableResource> $resolvableResources
     *
     * @return array<string|int, mixed>
     */
    private function loadResolvableResources(array $resolvableResources, string $loaderKey, ?string $locale): array
    {
        $resourceLoader = $this->resourceLoaderProvider->getResourceLoader($loaderKey);
        if (!$resourceLoader) {
            throw new \RuntimeException(\sprintf('ResourceLoader with key "%s" not found', $loaderKey));
        }

        $resourceIds = \array_map(fn (ResolvableResource $resource) => $resource->getId(), $resolvableResources);

        return $resourceLoader->load(
            $resourceIds,
            $locale,
        );
    }
}
