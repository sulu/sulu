<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when route already exists.
 */
class RouteAlreadyExistsException extends RestException
{
    /**
     * @var string
     */
    private $route;

    public function __construct($route)
    {
        parent::__construct(sprintf('Route "%s" already in exists.', $route), 9002);

        $this->route = $route;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}
