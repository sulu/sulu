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
 * Missing one of given parameter in api.
 */
class MissingParameterChoiceException extends RestException
{
    /**
     * @param string $controller
     * @param string[] $names
     */
    public function __construct(private $controller, private $names)
    {
        parent::__construct(\sprintf('Missing parameter "%s" in %s', \implode('" or "', $names), $controller), 0);
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string[]
     */
    public function getNames()
    {
        return $this->names;
    }
}
