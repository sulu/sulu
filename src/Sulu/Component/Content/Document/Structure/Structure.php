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
 * Lazy loading container for content properties.
 */
class Structure implements StructureInterface
{
    /**
     * @var array<string, mixed>
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $stagedData = [];

    public function getStagedData()
    {
        return $this->stagedData;
    }

    public function setStagedData(array $stagedData)
    {
        $this->stagedData = $stagedData;
    }

    public function commitStagedData($clearMissingContent)
    {
        $this->bind($this->stagedData, $clearMissingContent);
        $this->stagedData = [];
    }

    public function getProperty($name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        $property = new PropertyValue($name);
        $this->properties[$name] = $property;

        return $property;
    }

    public function getContentViewProperty($name)
    {
        throw new \Exception('Cannot retrieve content view property for non-managed property');
    }

    public function hasProperty($name)
    {
        return $this->offsetExists($name);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getProperty($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->getProperty($offset)->setValue($value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    public function toArray()
    {
        $values = [];
        foreach ($this->properties as $name => $property) {
            $values[$name] = $this->normalize($property->getValue());
        }

        return $values;
    }

    public function bind($data, $clearMissing = false)
    {
        foreach ($data as $key => $value) {
            $property = $this->getProperty($key);
            $property->setValue($value);
        }
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    protected function normalize($value)
    {
        if ($value instanceof PropertyValue) {
            $value = $value->getValue();
        }

        if (!\is_array($value)) {
            return $value;
        }

        $ret = [];
        foreach ($value as $key => $value) {
            $ret[$key] = $this->normalize($value);
        }

        return $ret;
    }
}
