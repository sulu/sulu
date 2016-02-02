<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * This exception is thrown if a required property for creating or manipulating
 * a filter attribute is missing.
 */
class MissingFilterAttributeException extends FilterException
{
    /**
     * The name of the attribute which is missing.
     *
     * @var string
     */
    private $attribute;

    public function __construct($attribute)
    {
        $this->attribute = $attribute;
        parent::__construct('The attribute with the name "' . $this->attribute . '" is missing.', 0);
    }

    /**
     * Returns the name of the missing attribute.
     *
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }
}
