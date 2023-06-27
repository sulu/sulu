<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

/**
 * parameter wrong datatype.
 */
class ParameterDataTypeException extends RestException
{
    /**
     * @param string $controller
     * @param string $name
     */
    public function __construct(private $controller, private $name)
    {
        parent::__construct(\sprintf('Parameter %s has wrong data type in %s', $name, $controller), 0);
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
