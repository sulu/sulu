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
 * Exception which is thrown when the name of a field in a condition does not match the field descriptors.
  */
class ConditionFieldNotFoundException extends FilterException
{
    /**
     * The name of the object not found.
     *
     * @var string
     */
    private $field;

    public function __construct($field)
    {
        $this->field = $field;
        parent::__construct('The condition field with the name "' . $this->field . '" could not be found.', 0);
    }

    /**
     * Returns the name of the entityname of the dependency not found.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }
}
