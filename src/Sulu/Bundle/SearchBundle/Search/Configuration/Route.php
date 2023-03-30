<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Configuration;

class Route
{
    private string $name;

    /**
     * @var string[]
     */
    private array $resultToRoute;

    public function __construct(
        string $name,
        array $resultToRoute
    ) {
        $this->name = $name;
        $this->resultToRoute = $resultToRoute;
    }
}
