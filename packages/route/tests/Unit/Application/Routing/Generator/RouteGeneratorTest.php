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
use Sulu\Route\Application\Routing\Generator\WebspaceRouteGeneratorInterface;
use Sulu\Route\Domain\Exception\MissingRequestContextParameterException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Translation\LocaleSwitcher;

#[CoversClass(RouteGenerator::class)]
class RouteGeneratorTest extends TestCase
{
    private RequestContext $requestContext;

    private RouteGenerator $routeGenerator;

    public function setUp(): void
    {
        $container = new Container();
        $container->set('the_site', new class() implements WebspaceRouteGeneratorInterface {
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

        $container->set('the_other_side', new class() implements WebspaceRouteGeneratorInterface {
            public function generate(RequestContext $requestContext, string $slug, string $locale): string
            {
                return \sprintf(
                    'https://example.org/%s%s',
                    $locale,
                    $slug,
                );
            }
        });

        // mirrors the real PageWebspaceRouteGenerator which resolves the webspace from the
        // request context, so the target webspace becomes observable in the generated url
        $container->set('.default', new class() implements WebspaceRouteGeneratorInterface {
            public function generate(RequestContext $requestContext, string $slug, string $locale): string
            {
                $webspace = $requestContext->getParameter('webspace');
                \assert(\is_string($webspace));

                return \sprintf(
                    'https://example.org/%s/%s%s',
                    $webspace,
                    $locale,
                    $slug,
                );
            }
        });

        $this->requestContext = new RequestContext();
        $this->routeGenerator = new RouteGenerator($container, $this->requestContext, new RequestStack(), new LocaleSwitcher('en', [], $this->requestContext));
    }

    public function testGenerate(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_site');
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateAbsoluteUrl(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_site', UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('http://localhost/en/test', $result);
    }

    public function testGenerateNetworkPath(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_site', UrlGeneratorInterface::NETWORK_PATH);
        $this->assertSame('//localhost/en/test', $result);
    }

    public function testGenerateRelativePath(): void
    {
        $this->requestContext->setPathInfo('/en/world/test');

        $result = $this->routeGenerator->generate('/test', 'en', 'the_site', UrlGeneratorInterface::RELATIVE_PATH);
        $this->assertSame('../test', $result);
    }

    public function testGenerateAbsolutePath(): void
    {
        $this->requestContext->setPathInfo('/en');

        $result = $this->routeGenerator->generate('/test', 'en', 'the_site', UrlGeneratorInterface::ABSOLUTE_PATH);
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateOther(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_other_side');
        $this->assertSame('https://example.org/en/test', $result);
    }

    public function testGenerateOtherAbsolutePath(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_other_side', UrlGeneratorInterface::ABSOLUTE_PATH);
        $this->assertSame('https://example.org/en/test', $result);
    }

    public function testGenerateOtherRelativePath(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_other_side', UrlGeneratorInterface::RELATIVE_PATH);
        $this->assertSame('https://example.org/en/test', $result);
    }

    public function testGenerateOtherNetworkPath(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'the_other_side', UrlGeneratorInterface::NETWORK_PATH);
        $this->assertSame('https://example.org/en/test', $result);
    }

    public function testGenerateRequestContextLocale(): void
    {
        $this->requestContext->setParameter('_locale', 'de');

        $result = $this->routeGenerator->generate('/test', null, 'the_site');
        $this->assertSame('/de/test', $result);
    }

    public function testGenerateRequestContextWebspace(): void
    {
        $this->requestContext->setParameter('webspace', 'the_site');

        $result = $this->routeGenerator->generate('/test', 'en', null);
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateUsesTargetWebspaceForDefaultGenerator(): void
    {
        // simulate a request handled in the context of "webspace-a"
        $this->requestContext->setParameter('webspace', 'webspace-a');

        // generate a route for a resource of a different target webspace "webspace-b"
        $result = $this->routeGenerator->generate('/test', 'en', 'webspace-b');

        $this->assertSame('https://example.org/webspace-b/en/test', $result);

        // the current request's webspace must be restored after generation
        $this->assertSame('webspace-a', $this->requestContext->getParameter('webspace'));
    }

    public function testGenerateRestoresMissingWebspaceParameter(): void
    {
        $result = $this->routeGenerator->generate('/test', 'en', 'webspace-b');

        $this->assertSame('https://example.org/webspace-b/en/test', $result);
        $this->assertNull($this->requestContext->getParameter('webspace'));
    }

    public function testGenerateRequestContextLocaleMissing(): void
    {
        $result = $this->routeGenerator->generate('/test', null, 'the_site');
        $this->assertSame('/en/test', $result);
    }

    public function testGenerateRequestContextWebspaceMissing(): void
    {
        $this->expectException(MissingRequestContextParameterException::class);
        $this->expectExceptionMessage('Missing request context parameter "webspace".');

        $this->routeGenerator->generate('/test', 'en', null);
    }
}
