<?php

namespace Sulu\Component\Content\Document\Property;

class Property
{
    private $value;
    private $name;

    public function __construct($name, $document)
    {
        $this->name = $name;
        $this->document = $document;
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

    /**
     * {@inheritDoc}
     */
    public function getDocument() 
    {
        return $this->document;
    }
    
}
