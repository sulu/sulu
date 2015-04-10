<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Property;

/**
 * Property definition and value
 */
interface PropertyInterface
{
    /**
     * Returns PHPCR name of template
     *
     * @return string
     */
    public function getName();

    /**
     * Sets the value from property
     *
     * @param $value mixed
     */
    public function setValue($value);

    /**
     * gets the value from property
     * @return mixed
     */
    public function getValue();

    /**
     * Return the document to which this property belongs
     *
     * @return object
     */
    public function getDocument();
}
  
