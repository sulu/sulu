<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $stagedData = [];

    /**
     * {@inheritdoc}
     */
    public function getStagedData()
    {
        return $this->stagedData;
    }

    /**
     * {@inheritdoc}
     */
    public function setStagedData(array $stagedData)
    {
        $this->stagedData = $stagedData;
    }

    /**
     * {@inheritdoc}
     */
    public function commitStagedData($clearMissingContent)
    {
        $this->bind($this->stagedData, $clearMissingContent);
        $this->stagedData = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        $property = new PropertyValue($name);
        $this->properties[$name] = $property;

        return $property;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentViewProperty($name)
    {
        throw new \Exception('Cannot retrieve content view property for non-managed property');
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->getProperty($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->getProperty($offset)->setValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $values = [];
        foreach ($this->properties as $name => $property) {
            $values[$name] = $this->normalize($property->getValue());
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
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

        if (!is_array($value)) {
            return $value;
        }

        $ret = [];
        foreach ($value as $key => $value) {
            $ret[$key] = $this->normalize($value);
        }

        return $ret;
    }
}
