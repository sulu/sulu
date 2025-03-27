<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Application;

use Sulu\Route\Application\Routing\Generator\SiteRouteGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @internal
 */
final class ExampleSiteRouteGenerator implements SiteRouteGeneratorInterface
{
    public function generate(RequestContext $requestContext, string $slug, string $locale): string
    {
        $port = match ($requestContext->getScheme()) {
            'http' => 80 !== $requestContext->getHttpPort() ? ':' . $requestContext->getHttpPort() : '',
            'https' => 443 !== $requestContext->getHttpsPort() ? ':' . $requestContext->getHttpsPort() : '',
            default => throw new \RuntimeException('Invalid scheme: ' . $requestContext->getScheme()),
        };

        return \sprintf(
            '%s://%s%s/%s%s',
            $requestContext->getScheme(),
            $requestContext->getHost(),
            $port,
            $locale,
            $slug,
        );
    }

    public static function getSite(): string
    {
        return 'sulu-io';
    }
}
