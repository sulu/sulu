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
 * Missing one of given parameter in api.
 */
class MissingParameterChoiceException extends RestException
{
    /**
     * @var string
     */
    private $names;
    /**
     * @var string
     */
    private $controller;

    /**
     * @param string $controller
     * @param string[] $names
     */
    public function __construct($controller, $names)
    {
        parent::__construct(sprintf('Missing parameter "%s" in %s', implode('" or "', $names), $controller), 0);

        $this->controller = $controller;
        $this->names = $names;
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
    public function getNames()
    {
        return $this->names;
    }
}
