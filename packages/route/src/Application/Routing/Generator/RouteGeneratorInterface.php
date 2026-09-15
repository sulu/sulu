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
use Sulu\Route\Domain\Exception\WebspaceUrlNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface RouteGeneratorInterface
{
    /**
     * @throws MissingRequestContextParameterException
     * @throws WebspaceUrlNotFoundException When no URL can be generated for the slug in the given locale and webspace
     */
    public function generate(string $slug, ?string $locale = null, ?string $webspace = null, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string;
}
