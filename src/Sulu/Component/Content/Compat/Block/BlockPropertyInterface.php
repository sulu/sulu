<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Block;

use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * interface definition for block property.
 *
 * @method BlockPropertyType[] getTypes()
 * @method addType(BlockPropertyType $type)
 * @method BlockPropertyType getType(string $name)
 * @method BlockPropertyType getProperties(int $index)
 * @method BlockPropertyType initProperties(int $index, string $typeName)
 */
interface BlockPropertyInterface extends PropertyInterface
{
}
