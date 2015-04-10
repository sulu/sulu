<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Structure\Structure;

/**
 * Lazy loading container for content properties.
 */
class PropertyContainer implements \ArrayAccess
{
    protected $properties = array();

    /**
     * Return the named property and evaluate its content
     *
     * @param string $name
     */
    public function getProperty($name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        $property = new Property(null, null);
        $this->properties[$name] = $property;

        return $property;
    }

    public function offsetExists($offset)
    {
        return $this->properties[$offset];
    }

    public function offsetGet($offset)
    {
        return $this->getProperty($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(
            'Cannot set content properties objects'
        );
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    public function getArrayCopy()
    {
        $values = array();
        foreach ($this->properties as $name => $property) {
            $values[$name] = $property->getValue();
        }

        return $values;
    }

    public function bind($data)
    {
        foreach ($data as $key => $value) {
            $property = $this->getProperty($key);
            $property->setValue($value);
        }
    }
}
