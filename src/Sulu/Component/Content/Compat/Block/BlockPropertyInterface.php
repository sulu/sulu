<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Block;

use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * interface definition for block property.
 */
interface BlockPropertyInterface extends PropertyInterface
{
    /**
     * returns a list of properties managed by this block.
     *
     * @return BlockPropertyType[]
     */
    public function getTypes();

    /**
     * adds a type.
     *
     * @param BlockPropertyType $type
     */
    public function addType(BlockPropertyType $type);

    /**
     * returns property with given name.
     *
     * @param string $name of property
     *
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     *
     * @return BlockPropertyType
     */
    public function getType($name);

    /**
     * returns properties for given index.
     *
     * @param int $index
     *
     * @return BlockPropertyType
     */
    public function getProperties($index);

    /**
     * Returns sizeof block.
     *
     * @return int
     */
    public function getLength();

    /**
     * initiate new child with given type name.
     *
     * @param int    $index
     * @param string $typeName
     *
     * @return BlockPropertyType
     */
    public function initProperties($index, $typeName);

    /**
     * clears all initialized properties.
     */
    public function clearProperties();

    /**
     * return default type name.
     *
     * @return string
     */
    public function getDefaultTypeName();
}
