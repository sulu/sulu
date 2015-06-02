<?php

namespace Sulu\Component\Content\Document\Structure;

/**
 * Value object for content type rendering.
 */
class Property
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $name;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get the property value
     * 
     * @return array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the property value
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * When would this ever be used??
     *
     * @param string $name
     */
    // public function setName($name)
    // {
    //     $this->name = $name;
    // }
}
