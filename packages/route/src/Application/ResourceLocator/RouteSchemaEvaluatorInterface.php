<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\ResourceLocator;

use Sulu\Route\Application\ResourceLocator\Exception\InvalidRouteSchemaException;

interface RouteSchemaEvaluatorInterface
{
    /**
     * @throws InvalidRouteSchemaException
     */
    public function evaluate(ResourceLocatorRequest $request): string;
}
