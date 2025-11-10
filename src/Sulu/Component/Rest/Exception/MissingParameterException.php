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
 * missing parameter in api.
 */
class MissingParameterException extends RestException
{
    /**
     * @param string $name
     * @param string $controller
     */
    public function __construct(private $controller, private $name)
    {
        parent::__construct(\sprintf('Missing parameter %s in %s', $name, $controller), 0);
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
