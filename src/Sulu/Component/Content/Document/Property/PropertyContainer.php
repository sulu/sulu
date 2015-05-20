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

use Sulu\Component\Content\Types\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Compat\Structure\Structure;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Document\Property\PropertyValue;
use Sulu\Component\Content\Document\Property\PropertyContainerInterface;

/**
 * Lazy loading container for content properties.
 */
class PropertyContainer implements PropertyContainerInterface
{
    /**
     * @var array
     */
    protected $properties = array();

    /**
     * @var array
     */
    protected $stagedData = array();

    /**
     * Get staged data, see documentation for commitStagedData
     *
     * @return array
     */
    public function getStagedData() 
    {
        return $this->stagedData;
    }
    
    /**
     * Set staged data, see documentation for commitStagedData
     *
     * @param array $stagedData
     */
    public function setStagedData($stagedData)
    {
        $this->stagedData = $stagedData;
    }

    /**
     * Commit the staged content data
     *
     * This is necessary because:
     *
     * - We cannot set the content data on a property-by-property basis
     * - Therefore the form framework needs to get/set to a specific property
     * - It uses the stagedData property for this purpose
     * - We then "commit" the staged data after the form has been submitted.
     *
     * We should refactor the content types so that they are not involved
     * in the process of mapping to PHPCR.
     *
     * If $clearMissingContent is true, then fields will be set to NULL
     *
     * @param boolean $clearMissingContent
     */
    public function commitStagedData($clearMissingContent)
    {
        $this->bind($this->stagedData, $clearMissingContent);
        $this->stagedData = array();
    }

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

        $property = new PropertyValue($name);
        $this->properties[$name] = $property;

        return $property;
    }

    public function hasProperty($name)
    {
        return $this->offsetExists($name);
    }

    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
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

    public function toArray()
    {
        $values = array();
        foreach ($this->properties as $name => $property) {
            $values[$name] = $this->normalize($property->getValue());
        }

        return $values;
    }

    protected function normalize($value)
    {
        if ($value instanceof PropertyValue) {
            $value = $value->getValue();
        }

        if (!is_array($value)) {
            return $value;
        }

        $ret = array();
        foreach ($value as $key => $value) {
            $ret[$key] = $this->normalize($value);
        }

        return $ret;
    }

    public function bind($data)
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
}
