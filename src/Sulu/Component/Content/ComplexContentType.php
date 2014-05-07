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
 * base class of complex content types
 */
abstract class ComplexContentType implements ContentTypeInterface
{
    /**
     * returns default parameters
     * @return array
     */
    public function getDefaultParams()
    {
        return array();
    }

    /**
     * magic getter for twig templates
     * @param $property
     * @return mixed|null
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
