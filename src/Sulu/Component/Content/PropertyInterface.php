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
 * Property definition and value
 */
interface PropertyInterface
{
    /**
     * returns name of template
     * @return string
     */
    public function getName();

    /**
     * returns mandatory
     * @return bool
     */
    public function isMandatory();

    /**
     * returns multilingual
     * @return bool
     */
    public function isMultilingual();

    /**
     * return min occurs
     * @return int
     */
    public function getMinOccurs();

    /**
     * return max occurs
     * @return int
     */
    public function getMaxOccurs();

    /**
     * returns name of content type
     * @return string
     */
    public function getContentTypeName();

    /**
     * parameter of property
     * @return array
     */
    public function getParams();

    /**
     * sets the value from property
     * @param $value mixed
     */
    public function setValue($value);

    /**
     * gets the value from property
     * @return mixed
     */
    public function getValue();

    /**
     * returns TRUE if property is a block
     * @return boolean
     */
    public function getIsBlock();

    /**
     * returns TRUE if property is multiple
     * @return bool
     */
    public function getIsMultiple();
}
