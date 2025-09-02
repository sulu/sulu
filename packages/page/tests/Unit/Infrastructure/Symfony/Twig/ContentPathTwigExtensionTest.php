<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Symfony\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\ContentPathTwigExtension;
use Sulu\Route\Application\Routing\Generator\RouteGenerator;
use Sulu\Route\Application\Routing\Generator\SiteRouteGeneratorInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\TwigFunction;

#[CoversClass(ContentPathTwigExtension::class)]
class ContentPathTwigExtensionTest extends TestCase
{
    private RouteGenerator $routeGenerator;

    private RequestContext $requestContext;

    private LocaleSwitcher $localeSwitcher;

    private RequestStack $requestStack;

    private ContentPathTwigExtension $extension;

    protected function setUp(): void
    {
        $this->requestContext = new RequestContext();
        $this->requestStack = new RequestStack();
        $this->localeSwitcher = new LocaleSwitcher(
            'en',
            [],
            $this->requestContext,
        );

        $websiteRouteGenerator = new class() implements SiteRouteGeneratorInterface {
            public function generate(RequestContext $requestContext, string $slug, string $locale): string
            {
                $port = match ($requestContext->getScheme()) {
                    'http' => 80 !== $requestContext->getHttpPort() ? ':' . $requestContext->getHttpPort() : '',
                    'https' => 443 !== $requestContext->getHttpsPort() ? ':' . $requestContext->getHttpsPort() : '',
                    default => throw new \RuntimeException('Invalid scheme: ' . $requestContext->getScheme()),
                };

                return \rtrim(\sprintf(
                    '%s://%s%s/%s%s',
                    $requestContext->getScheme(),
                    $requestContext->getHost(),
                    $port,
                    $locale,
                    $slug,
                ), '/');
            }
        };

        $intranetRouteGenerator = new class() implements SiteRouteGeneratorInterface {
            public function generate(RequestContext $requestContext, string $slug, string $locale): string
            {
                $port = match ($requestContext->getScheme()) {
                    'http' => 80 !== $requestContext->getHttpPort() ? ':' . $requestContext->getHttpPort() : '',
                    'https' => 443 !== $requestContext->getHttpsPort() ? ':' . $requestContext->getHttpsPort() : '',
                    default => throw new \RuntimeException('Invalid scheme: ' . $requestContext->getScheme()),
                };

                return \rtrim(\sprintf(
                    '%s://intranet.localhost%s/%s%s',
                    $requestContext->getScheme(),
                    $port,
                    $locale,
                    $slug,
                ), '/');
            }
        };

        $container = new Container();
        $container->set('website', $websiteRouteGenerator);
        $container->set('intranet', $intranetRouteGenerator);

        $this->routeGenerator = new RouteGenerator(
            $container,
            $this->requestContext,
            $this->requestStack,
            $this->localeSwitcher,
        );

        $this->extension = new ContentPathTwigExtension($this->routeGenerator);
    }

    public function testGetFunctions(): void
    {
        $this->assertSame(
            [
                'sulu_content_path',
                'sulu_content_root_path',
            ],
            \array_map(fn (TwigFunction $function) => $function->getName(), $this->extension->getFunctions()),
        );
    }

    #[TestWith(['/en/test', '/test'])]
    #[TestWith(['http://intranet.localhost/en/test', '/test', 'intranet'])]
    #[TestWith(['/de/test', '/test', null, 'de'])]
    #[TestWith(['http://intranet.localhost/de/test', '/test', 'intranet', 'de'])]
    public function testSuluContentPath(string $expectedUrl, string $slug, ?string $webspaceKey = null, ?string $locale = null): void
    {
        $this->requestContext->setParameter(RequestAttributeEnum::SITE->value, 'website');

        $this->assertSame(
            $expectedUrl,
            $this->extension->suluContentPath($slug, $webspaceKey, $locale)
        );
    }

    public function testSuluContentRootPath(): void
    {
        $this->requestContext->setParameter(RequestAttributeEnum::SITE->value, 'website');

        $this->assertSame(
            '/en',
            $this->extension->suluContentRootPath(),
        );
    }
}
