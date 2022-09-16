<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Structure;

/**
 * Value object for content type rendering.
 *
 * Note this would more appropriately be named "Property" but that potentially confuses
 * things even more whilst the Compat\\ namespace exists. In addition, this class may
 * not be long lived after we change the content mapping logic.
 */
class PropertyValue implements \ArrayAccess
{
    private $value;

    private $name;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return \is_array($this->value) && isset($this->value[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!\is_array($this->value)) {
            return;
        }

        return $this->value[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (!\is_array($this->value)) {
            return;
        }

        $this->value[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if (!\is_array($this->value)) {
            return;
        }

        unset($this->value[$offset]);
    }
}
