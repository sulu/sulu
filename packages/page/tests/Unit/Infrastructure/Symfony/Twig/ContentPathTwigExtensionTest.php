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
use Sulu\Route\Application\Routing\Generator\WebspaceRouteGeneratorInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
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

    private UrlMatcherInterface $urlMatcher;

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

        $websiteRouteGenerator = new class() implements WebspaceRouteGeneratorInterface {
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

        $intranetRouteGenerator = new class() implements WebspaceRouteGeneratorInterface {
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

        $this->urlMatcher = $this->createNoMatchUrlMatcher();

        $this->extension = new ContentPathTwigExtension($this->routeGenerator, $this->urlMatcher);
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
        $this->requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, 'website');

        $this->assertSame(
            $expectedUrl,
            $this->extension->suluContentPath($slug, $webspaceKey, $locale)
        );
    }

    public function testSuluContentRootPath(): void
    {
        $this->requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, 'website');

        $this->assertSame(
            '/en',
            $this->extension->suluContentRootPath(),
        );
    }

    public function testSuluContentPathSkipsSymfonyControllerRoute(): void
    {
        $matchingUrlMatcher = $this->createMatchingUrlMatcher([
            '_controller' => 'App\Controller\MediaController::download',
            '_route' => 'sulu_media.website.media.download',
        ]);

        $extension = new ContentPathTwigExtension($this->routeGenerator, $matchingUrlMatcher);

        $this->assertSame(
            '/media/123/download/image.jpg',
            $extension->suluContentPath('/media/123/download/image.jpg'),
        );
    }

    public function testSuluContentPathDoesNotSkipExternalUrl(): void
    {
        $this->assertSame(
            'https://example.com/page',
            $this->extension->suluContentPath('https://example.com/page'),
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createMatchingUrlMatcher(array $parameters): UrlMatcherInterface
    {
        return new class($parameters) implements UrlMatcherInterface {
            /**
             * @param array<string, mixed> $parameters
             */
            public function __construct(private readonly array $parameters)
            {
            }

            /** @return array<string, mixed> */
            public function match(string $pathinfo): array
            {
                return $this->parameters;
            }

            public function setContext(RequestContext $context): void
            {
            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };
    }

    private function createNoMatchUrlMatcher(): UrlMatcherInterface
    {
        return new class() implements UrlMatcherInterface {
            /** @return array<string, mixed> */
            public function match(string $pathinfo): array
            {
                throw new ResourceNotFoundException();
            }

            public function setContext(RequestContext $context): void
            {
            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };
    }
}
