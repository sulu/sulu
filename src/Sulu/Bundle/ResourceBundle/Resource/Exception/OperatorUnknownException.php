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
 * OperatorUnknownException is thrown when the given operator is unknown.
 */
class OperatorUnknownException extends OperatorException
{
    /**
     * @var string
     */
    protected $operator;

    public function __construct($operator)
    {
        parent::__construct('The given operator ' . $operator . ' is unknown!');
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
}
