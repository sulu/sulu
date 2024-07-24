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
    /**
     * @param string[] $resultToRoute
     */
    public function __construct(
        private string $name,
        private array $resultToRoute
    ) {
    }
}
