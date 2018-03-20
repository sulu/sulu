<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata;

use Sulu\Bundle\AdminBundle\Exception\ResourceNotFoundException;

class ResourceMetadataPool
{
    /**
     * @var ResourceMetadataProviderInterface[]
     */
    private $resourceMetadataProviders;

    /**
     * Returns all the registered providers.
     *
     * @return ResourceMetadataProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->resourceMetadataProviders;
    }

    public function getResourceMetadata(string $resourceKey, string $locale): ?ResourceMetadataInterface
    {
        foreach ($this->resourceMetadataProviders as $resourceMetadataProvider) {
            $resourceMetadata = $resourceMetadataProvider->getResourceMetadata($resourceKey, $locale);

            if ($resourceMetadata) {
                return $resourceMetadata;
            }
        }

        throw new ResourceNotFoundException($resourceKey);
    }

    public function addResourceMetadataProvider(ResourceMetadataProviderInterface $resourceMetadataProvider): void
    {
        $this->resourceMetadataProviders[] = $resourceMetadataProvider;
    }
}
