<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Property of Structure generated from Structure Manager to map a template
 */
class Property implements PropertyInterface
{
    /**
     * name of property
     * @var string
     */
    private $name;

    /**
     * is property mandatory
     * @var bool
     */
    private $mandatory;

    /**
     * is property multilingual
     * @var bool
     */
    private $multilingual;

    /**
     * min occurs of property value
     * @var int
     */
    private $minOccurs;

    /**
     * max occurs of property value
     * @var int
     */
    private $maxOccurs;

    /**
     * name of content type
     * @var string
     */
    private $contentTypeName;

    /**
     * parameter of property to merge with parameter of content type
     * @var array
     */
    private $params;

    /**
     * value of property
     * @var mixed
     */
    private $value;

    function __construct(
        $name,
        $contentTypeName,
        $mandatory = false,
        $multilingual = false,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = array()
    ) {
        $this->contentTypeName = $contentTypeName;
        $this->mandatory = $mandatory;
        $this->maxOccurs = $maxOccurs;
        $this->minOccurs = $minOccurs;
        $this->multilingual = $multilingual;
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * returns name of template
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns mandatory
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * returns multilingual
     * @return bool
     */
    public function isMultilingual()
    {
        return $this->multilingual;
    }

    /**
     * return min occurs
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    /**
     * return max occurs
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * sets the value from property
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * gets the value from property
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * returns name of content type
     * @return string
     */
    public function getContentTypeName()
    {
        return $this->contentTypeName;
    }

    /**
     * parameter of property
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * returns TRUE if property is a block
     * @return boolean
     */
    public function getIsBlock()
    {
        return false;
    }

    /**
     * returns TRUE if property is multiple
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->minOccurs > 1 || $this->maxOccurs > 1;
    }

    /**
     * magic getter for twig templates
     * @param $property
     * @return null
     */
    public function __get($property)
    {
        if (method_exists($this, 'get' . ucfirst($property))) {
            return $this->{'get' . ucfirst($property)}();
        } else {
            return null;
        }
    }
}
