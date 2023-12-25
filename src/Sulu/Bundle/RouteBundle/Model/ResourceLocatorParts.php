<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Model;

/**
 * @extends \ArrayObject<string, mixed>
 */
class ResourceLocatorParts extends \ArrayObject
{
    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->offsetGet(\lcfirst(\substr($name, 3)));
    }
}
