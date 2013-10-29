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
     * @return bool
     */
    public function isMandatory();

    /**
     * @return bool
     */
    public function isMultilingual();

    /**
     * @return int
     */
    public function getMinOccurs();

    /**
     * @return int
     */
    public function getMaxOccurs();

    /**
     * @return string
     */
    public function getContentTypeName();

    /**
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
}
