<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\ResourceMetadata;

use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataProviderInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;

class StructureResourceMetadataProvider implements ResourceMetadataProviderInterface
{
    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @var array
     */
    private $resources;

    public function __construct(
        StructureMetadataFactory $structureMetadataFactory,
        array $resources
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllResourceMetadata(string $locale): array
    {
        $resourceMetadataArray = [];

        foreach (array_keys($this->resources) as $resourceKey) {
            $resourceMetadataArray[] = $this->getResourceMetadata($resourceKey, $locale);
        }

        return $resourceMetadataArray;
    }

    public function getResourceMetadata(string $resourceKey, string $locale): ?ResourceMetadataInterface
    {
        if (!array_key_exists($resourceKey, $this->resources)) {
            return null;
        }

        return $this->getResourceMetadataForStructure(
            $resourceKey,
            $this->resources[$resourceKey]['endpoint'],
            $locale
        );
    }

    private function getResourceMetadataForStructure(
        string $resourceKey,
        string $endpoint,
        string $locale
    ): ResourceMetadata {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata->setKey($resourceKey);
        $resourceMetadata->setEndpoint($endpoint);

        return $resourceMetadata;
    }
}
