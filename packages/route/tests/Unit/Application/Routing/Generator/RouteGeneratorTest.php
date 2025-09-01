<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\Routing\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Route\Application\Routing\Generator\RouteGenerator;
use Sulu\Route\Application\Routing\Generator\SiteRouteGeneratorInterface;
use Sulu\Route\Domain\Exception\MissingRequestContextParameterException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

#[CoversClass(RouteGenerator::class)]
class RouteGeneratorTest extends TestCase
{
    private RequestContext $requestContext;

    private RouteGenerator $routeGenerator;

    public function setUp(): void
    {
        $container = new Container();
        $container->set('the_site', new class() implements SiteRouteGeneratorInterface {
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
        });

        $container->set('the_other_side', new class() implements SiteRouteGeneratorInterface {
            public function generate(RequestContext $requestContext, string $slug, string $locale): string
            {
                return \sprintf(
                    'https://example.org/%s%s',
                    $locale,
                    $slug,
                );
            }
        });

        $this->requestContext = new RequestContext();
        $this->routeGenerator = new RouteGenerator($container, $this->requestContext, new RequestStack());
    }

    public function testGenerate(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_site');
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateOther(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_other_side');
        $this->assertSame('https://example.org/en/test', $result);
    }

    public function testGenerateRequestContextLocale(): void
    {
        $this->requestContext->setParameter('_locale', 'en');

        $result = $this->routeGenerator->generate('/test', null, 'the_site');
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateRequestContextSite(): void
    {
        $this->requestContext->setParameter('site', 'the_site');

        $result = $this->routeGenerator->generate('/test', 'en', null);
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateRequestContextLocaleMissing(): void
    {
        $this->expectException(MissingRequestContextParameterException::class);
        $this->expectExceptionMessage('Missing request context parameter "_locale".');

        $this->routeGenerator->generate('/test', null, 'default');
    }

    public function testGenerateRequestContextSiteMissing(): void
    {
        $this->expectException(MissingRequestContextParameterException::class);
        $this->expectExceptionMessage('Missing request context parameter "site".');

        $this->routeGenerator->generate('/test', 'en', null);
    }
}
