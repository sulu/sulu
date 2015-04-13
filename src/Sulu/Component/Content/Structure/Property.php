<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

class Property extends Item implements PropertyInterface
{
    /**
     * Type of this property (e.g. "text_line", "smart_content")
     *
     * @var string
     */
    public $type;

    /**
     * If the property should be available in different localizations
     *
     * @var boolean
     */
    public $localized = false;

    /**
     * If the property is required
     *
     * @var boolean
     */
    public $required = false;

    /**
     * The number of grid columns the property should use in the admin interface
     *
     * @var integer
     */
    public $colSpan = null;

    /**
     * The CSS class the property should use in the admin interface
     *
     * @var string
     */
    public $cssClass = null;

    /**
     * Tags, e.g. [['name' => 'sulu_search.field', 'type' => 'string']]
     *
     * @var array
     */
    public $tags = array();

    /**
     * @var integer
     */
    public $minOccurs = 1;

    /**
     * @var mixed
     */
    public $maxOccurs = 1;

    /**
     * @var Structure
     */
    public $structure;


    /**
     * @deprecated This should never be instantiated by anything other than the XML loader
     *             and the XML loader does not use the constructor.
     */
    public function __construct(
        $name = null,
        $metaData = null,
        $contentTypeName = null,
        $mandatory = false,
        $multilingual = true,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = array(),
        $tags = array(),
        $col = null
    ) {
        $this->type = $contentTypeName;
        $this->required = $mandatory;
        $this->maxOccurs = $maxOccurs;
        $this->minOccurs = $minOccurs;
        $this->localized = $multilingual;
        $this->name = $name;
        $this->parameters = $params;
        $this->tags = $tags;
        $this->colSpan = $col;
    }

    public function getIsMultiple()
    {
        return $this->isMultiple();
    }

    /**
     * @deprecated - use isRequired
     */
    public function getMandatory()
    {
        return $this->required;
    }

    /**
     * @deprecated - use isLocalized
     */
    public function isMultilingual()
    {
        return $this->localized;
    }

    /**
     * TODO: Remove this, it is a duplicate
     */
    public function getMultilingual()
    {
        return $this->localized;
    }

    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    public function getStructure() 
    {
        throw new \InvalidArgumentException(
            'Not implemented'
        );
    }

    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * @deprecated - use getType
     */
    public function getContentTypeName() 
    {
        return $this->type;
    }

    /**
     * @deprecated
     */
    public function getIsBlock() 
    {
        return false;
    }

    public function getColSpan() 
    {
        return $this->colSpan;
    }

    public function getPlaceholder($locale) 
    {
        return $this->placeholder[$locale];
    }

    /**
     * Duplicate
     *
     * @deprecated - use isRequired
     */
    public function isMandatory()
    {
        return $this->required;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function isMultiple()
    {
        return $this->minOccurs !== $this->maxOccurs;
    }

    public function isLocalized()
    {
        return $this->localized;
    }

    public function getType() 
    {
        return $this->type;
    }
    
}
