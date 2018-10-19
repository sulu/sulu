<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ResourceMetadata;

use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataMapper;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataProviderInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\Type;
use Sulu\Bundle\AdminBundle\ResourceMetadata\TypedResourceMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureResourceMetadataProvider implements ResourceMetadataProviderInterface
{
    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @var ResourceMetadataMapper
     */
    private $resourceMetadataMapper;

    /**
     * @var array
     */
    private $resources;

    public function __construct(
        StructureMetadataFactory $structureMetadataFactory,
        ResourceMetadataMapper $resourceMetadataMapper,
        array $resources
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->resourceMetadataMapper = $resourceMetadataMapper;
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
            $this->resources[$resourceKey]['datagrid'],
            $this->resources[$resourceKey]['types'],
            $this->resources[$resourceKey]['endpoint'],
            $locale
        );
    }

    private function getResourceMetadataForStructure(
        string $resourceKey,
        string $list,
        array $structureTypes,
        string $endpoint,
        string $locale
    ): TypedResourceMetadata {
        $resourceMetadata = new TypedResourceMetadata();
        $resourceMetadata->setKey($resourceKey);
        $resourceMetadata->setDatagrid($this->resourceMetadataMapper->mapDatagrid($list, $locale));
        $resourceMetadata->setEndpoint($endpoint);

        foreach ($structureTypes as $structureType) {
            /** @var StructureMetadata $structureMetadata */
            foreach ($this->structureMetadataFactory->getStructures($structureType) as $structureMetadata) {
                if ($structureMetadata->isInternal()) {
                    continue;
                }

                // check if type was already added
                // this is needed because we have two types which are pointing to the same directory
                if (array_key_exists($structureMetadata->getName(), $resourceMetadata->getTypes())) {
                    continue;
                }

                $type = new Type($structureMetadata->getName());
                $type->setTitle($structureMetadata->getTitle($locale) ?? ucfirst($structureMetadata->getName()));
                $type->setForm($this->resourceMetadataMapper->mapForm($structureMetadata->getChildren(), $locale));

                $schema = $this->resourceMetadataMapper->mapSchema($structureMetadata->getProperties());
                $templateSchema = $structureMetadata->getSchema();
                if ($templateSchema) {
                    $schema = $schema->merge($templateSchema);
                }

                $type->setSchema($schema);

                $resourceMetadata->addType($type);
            }
        }

        return $resourceMetadata;
    }
}
