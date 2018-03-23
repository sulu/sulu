<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\ResourceMetadata;

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

    public function getResourceMetadata(string $resourceKey, string $locale): ?ResourceMetadataInterface
    {
        if (!array_key_exists($resourceKey, $this->resources)) {
            return null;
        }

        return $this->getResourceMetadataForStructure(
            $this->resources[$resourceKey]['datagrid'],
            $this->resources[$resourceKey]['types'],
            $locale
        );
    }

    private function getResourceMetadataForStructure(
        string $list,
        array $structureTypes,
        string $locale
    ): TypedResourceMetadata {
        $resourceMetadata = new TypedResourceMetadata();
        $resourceMetadata->setDatagrid($this->resourceMetadataMapper->mapDatagrid($list, $locale));

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
                $type->setTitle($structureMetadata->getTitle($locale));
                $type->setForm($this->resourceMetadataMapper->mapForm($structureMetadata->getChildren(), $locale));
                $type->setSchema($this->resourceMetadataMapper->mapSchema($structureMetadata->getProperties()));

                $resourceMetadata->addType($type);
            }
        }

        return $resourceMetadata;
    }
}
