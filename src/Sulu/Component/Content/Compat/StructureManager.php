<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache.
 */
class StructureManager implements StructureManagerInterface
{
    use ContainerAwareTrait;

    private $structureFactory;
    private $inspector;
    private $propertyFactory;
    private $typeMap;

    /**
     * @param StructureMetadataFactory $structureFactory
     * @param DocumentInspector $inspector
     * @param LegacyPropertyFactory $propertyFactory
     * @param array $typeMap
     */
    public function __construct(
        StructureMetadataFactory $structureFactory,
        DocumentInspector $inspector,
        LegacyPropertyFactory $propertyFactory,
        array $typeMap
    ) {
        $this->structureFactory = $structureFactory;
        $this->inspector = $inspector;
        $this->propertyFactory = $propertyFactory;
        $this->typeMap = $typeMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructure($key, $type = Structure::TYPE_PAGE)
    {
        return $this->wrapStructure($type, $this->structureFactory->getStructureMetadata($type, $key));
    }

    /**
     * {@inheritdoc}
     */
    public function getStructures($type = Structure::TYPE_PAGE)
    {
        $wrappedStructures = [];
        $structures = $this->structureFactory->getStructures($type);

        foreach ($structures as $structure) {
            $wrappedStructures[] = $this->wrapStructure($type, $structure);
        }

        return $wrappedStructures;
    }

    /**
     * {@inheritdoc}
     */
    public function wrapStructure($type, StructureMetadata $structure)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid legacy type "%s", known types: "%s"',
                    $type,
                    implode('", "', array_keys($this->typeMap))
                )
            );
        }

        $class = $this->typeMap[$type];

        return new $class($structure, $this->inspector, $this->propertyFactory);
    }
}
