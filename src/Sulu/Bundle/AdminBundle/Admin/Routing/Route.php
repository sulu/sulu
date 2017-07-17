<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

/**
 * Represents a route for adminstration frontend.
 */
class Route
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $view;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $parameters;

    public function __construct(string $name, string $path, string $view, array $parameters = [])
    {
        $this->name = $name;
        $this->path = $path;
        $this->view = $view;
        $this->parameters = $parameters;
    }
}
