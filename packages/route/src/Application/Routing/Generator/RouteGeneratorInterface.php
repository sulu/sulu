<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Routing\Generator;

use Sulu\Route\Domain\Exception\MissingRequestContextParameterException;

interface RouteGeneratorInterface
{
    /**
     * @throws MissingRequestContextParameterException
     */
    public function generate(string $slug, ?string $locale = null, ?string $site = null): string;
}
