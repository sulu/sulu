<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $controller;

    public function __construct($controller, $name)
    {
        parent::__construct(sprintf('Parameter %s has wrong data type in %s', $name, $controller), 0);
        $this->controller = $controller;
        $this->name = $name;
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
