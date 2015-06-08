<?php

namespace Sulu\Component\Content\Document\Structure;

/**
 * Value object for content type rendering.
 *
 * Note this would more appropriately be named "Property" but that potentially confuses
 * things even more whilst the Compat\\ namespace exists. In addition, this class may
 * not be long lived after we change the content mapping logic.
 */
class PropertyValue
{
    private $value;
    private $name;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
