<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Routing\Matcher;

use Sulu\Route\Domain\Model\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

interface RouteDefaultsProviderInterface
{
    /**
     * @return array<string, mixed>
     *
     * @throws ResourceNotFoundException if the resource of the route cannot be served
     */
    public function getDefaults(Route $route): array;
}
