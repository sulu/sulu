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

    public function __construct(string $name, string $view, string $path, array $parameters = [])
    {
        $this->name = $name;
        $this->view = $view;
        $this->path = $path;
        $this->parameters = $parameters;
    }
}
