<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Content\StructureSerializer;

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Structure;

/**
 * Serializer for structures
 */
interface StructureSerializerInterface
{
    /**
     * Serializes given structure to array
     * @param StructureInterface $structure
     * @return array
     */
    public function serialize(StructureInterface $structure);

    /**
     * Deserializes data to structure
     * @param array $data
     * @param string $type
     * @return StructureInterface
     */
    public function deserialize(array $data, $type = Structure::TYPE_PAGE);
}
