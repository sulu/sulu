<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\SmartContent\Exception\NoSuchPropertyException;
use Sulu\Component\SmartContent\Exception\NotSupportedException;

/**
 * Base class for DataProvider items.
 *
 * @ExclusionPolicy("all")
 */
class ArrayAccessItem implements ResourceItemInterface, \ArrayAccess, \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var object
     */
    private $resource;

    /**
     * @param object $resource
     */
    public function __construct($id, array $data, $resource)
    {
        $this->id = $id;
        $this->data = $data;
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @VirtualProperty()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns TRUE if data array contains given key.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function exists($key)
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * Returns value with given key.
     *
     * @param string $key
     *
     * @throws NoSuchPropertyException
     */
    protected function get($key)
    {
        if (!$this->exists($key)) {
            throw new NoSuchPropertyException();
        }

        return $this->data[$key];
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new NotSupportedException();
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new NotSupportedException();
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }
}
