<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Translation;

use Sulu\Component\Content\PropertyInterface;

/**
 * Wrapper for translated properties
 * @package Sulu\Component\Content\Mapper\Translation
 */
class TranslatedProperty implements PropertyInterface
{
    /**
     * @var \Sulu\Component\Content\PropertyInterface
     */
    private $property;

    /**
     * @var string
     */
    private $localization;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @param PropertyInterface $property
     * @param string $localization
     * @param string $languageNamespace
     */
    public function __construct(PropertyInterface $property, $localization, $languageNamespace)
    {
        $this->property = $property;
        $this->localization = $localization;
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * @return \Sulu\Component\Content\PropertyInterface
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * returns name of template
     * @return string
     */
    public function getName()
    {
        return $this->languageNamespace . ':' . $this->localization . '-' . $this->property->getName();
    }

    /**
     * returns mandatory
     * @return bool
     */
    public function isMandatory()
    {
        return $this->property->isMandatory();
    }

    /**
     * returns multilingual
     * @return bool
     */
    public function isMultilingual()
    {
        return $this->property->isMultilingual();
    }

    /**
     * return min occurs
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->property->getMinOccurs();
    }

    /**
     * return max occurs
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->property->getMaxOccurs();
    }

    /**
     * returns name of content type
     * @return string
     */
    public function getContentTypeName()
    {
        return $this->property->getContentTypeName();
    }

    /**
     * parameter of property
     * @return array
     */
    public function getParams()
    {
        return $this->property->getParams();
    }

    /**
     * sets the value from property
     * @param $value mixed
     */
    public function setValue($value)
    {
        $this->property->setValue($value);
    }

    /**
     * gets the value from property
     * @return mixed
     */
    public function getValue()
    {
        return $this->property->getValue();
    }

    /**
     * sets the localization of this property
     * @param string $localization
     */
    public function setLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * returns the localization of this property
     * @return string
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * returns TRUE if property is a block
     * @return boolean
     */
    public function getIsBlock()
    {
        return $this->getProperty()->getIsBlock();
    }

    /**
     * returns TRUE if property is multiple
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->property->getIsMultiple();
    }
}
