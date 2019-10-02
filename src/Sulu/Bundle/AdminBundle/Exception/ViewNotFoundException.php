<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

/**
 * An instance of this exception signals that no route with given name was found.
 */
class ViewNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $route;

    public function __construct(string $route)
    {
        parent::__construct(sprintf('The route with the name "%s" does not exist.', $route));

        $this->route = $route;
    }

    public function getRoute(): string
    {
        return $this->route;
    }
}
