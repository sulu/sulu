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

use Symfony\Component\Routing\RequestContext;

interface SiteRouteGeneratorInterface
{
    public function generate(RequestContext $requestContext, string $slug, string $locale): string;
}
