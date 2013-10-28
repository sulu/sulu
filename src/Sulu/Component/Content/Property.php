<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class Property implements PropertyInterface
{

    private $name;
    private $mandatory;
    private $multilingual;
    private $minOccurs;
    private $maxOccurs;

    private $params;
    private $value;

    /**
     * @param array $params params of property
     */
    function __construct($params = array())
    {
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
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @return bool
     */
    public function isMultilingual()
    {
        return $this->multilingual;
    }

    /**
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    /**
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * sets the value from property
     * @param $value mixed
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

}
