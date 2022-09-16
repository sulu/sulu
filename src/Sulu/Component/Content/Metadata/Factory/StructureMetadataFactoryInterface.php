<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Factory;

use Sulu\Component\Content\Metadata\StructureMetadata;

interface StructureMetadataFactoryInterface
{
    /**
     * Return the structure of the given $type and $structureType.
     *
     * @param mixed $type The primary system type, e.g. page, snippet
     * @param mixed $structureType The secondary user type
     *
     * @return StructureMetadata|null
     *
     * @throws Exception\StructureTypeNotFoundException If the structure was not found
     * @throws Exception\DocumentTypeNotFoundException If the document type was not mapped
     */
    public function getStructureMetadata($type, $structureType);

    /**
     * Return all structures of the given type.
     *
     * @param string $type
     *
     * @return StructureMetadata[]
     */
    public function getStructures($type);

    /**
     * @return string[]
     */
    public function getStructureTypes(): array;

    /**
     * Return true if the given type has been registered with the structure factory.
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasStructuresFor($type);
}
