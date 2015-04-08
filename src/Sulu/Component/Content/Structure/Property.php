<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Structure;

class Property extends Item
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

    public function __set($field, $value)
    {
        throw new \InvalidArgumentException(sprintf(
            'Property "%s" does not exist on "%s"',
            $field, get_class($this)
        ));
    }

    public function isMultiple()
    {
        return $this->minOccurs !== $this->maxOccurs;
    }
}
