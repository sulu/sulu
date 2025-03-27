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

interface RouteDefaultsProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getDefaults(Route $route): array;
}
