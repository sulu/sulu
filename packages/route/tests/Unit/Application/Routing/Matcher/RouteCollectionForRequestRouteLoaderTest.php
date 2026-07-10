<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\Routing\Matcher;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Route\Application\Routing\Matcher\RouteCollectionForRequestRouteLoader;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

#[CoversClass(RouteCollectionForRequestRouteLoader::class)]
class RouteCollectionForRequestRouteLoaderTest extends TestCase
{
    use SetGetPrivatePropertyTrait;
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    private RequestContext $requestContext;

    private RouteCollectionForRequestRouteLoader $routeCollectionForRequestRouteLoader;

    protected function setUp(): void
    {
        $container = new Container();
        $container->set('resource_key_example', new class() implements RouteDefaultsProviderInterface {
            public function getDefaults(Route $route): array
            {
                return [
                    '_controller' => 'example.controller',
                ];
            }
        });

        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->requestContext = new RequestContext();

        $this->routeCollectionForRequestRouteLoader = new RouteCollectionForRequestRouteLoader(
            $this->routeRepository->reveal(),
            $container,
            $this->requestContext,
        );
    }

    public function testGetRouteCollectionForRequestIncorrectWebspace(): void
    {
        $request = Request::create('/en/test');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, new \stdClass());

        $this->routeRepository->findFirstBy(Argument::cetera())->shouldNotBeCalled();
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestNoSlug(): void
    {
        $request = Request::create('/en/test');
        $request->attributes->set(RequestAttributeEnum::WEBSPACE->value, 'the_site');

        $this->routeRepository->findFirstBy(Argument::cetera())->shouldNotBeCalled();
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestNoRoute(): void
    {
        $request = Request::create('/test');
        $request->attributes->set(RequestAttributeEnum::WEBSPACE->value, 'the_site');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, '/test');

        $this->routeRepository->findFirstBy(Argument::cetera())->willReturn(null);
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestHomepageWithTrailingSlashDoesNotMatch(): void
    {
        $request = Request::create('/de/');
        $request->attributes->set('_sulu', $this->createSuluAttributes('/', '/de'));

        $this->routeRepository->findFirstBy(Argument::cetera())->shouldNotBeCalled();
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestHomepageOfPrefixedPortalMatches(): void
    {
        // the homepage has no resourceLocator, the RequestAttributes filter out its empty string
        $request = Request::create('/de');
        $request->attributes->set('_sulu', $this->createSuluAttributes(null, '/de'));

        $routeModel = new Route('resource_key_example', '1', 'en', '/', 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $this->routeRepository->findFirstBy(['webspaceOrNull' => 'the_site', 'locale' => 'en', 'slug' => '/'], Argument::cetera())
            ->willReturn($routeModel);
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routeCollection);
    }

    public function testGetRouteCollectionForRequestHomepageOfPortalWithoutPrefixMatches(): void
    {
        // without a prefix "/" is the canonical homepage url, it must keep matching
        $request = Request::create('/');
        $request->attributes->set('_sulu', $this->createSuluAttributes('/', null));

        $routeModel = new Route('resource_key_example', '1', 'en', '/', 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $this->routeRepository->findFirstBy(['webspaceOrNull' => 'the_site', 'locale' => 'en', 'slug' => '/'], Argument::cetera())
            ->willReturn($routeModel);
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routeCollection);
    }

    private function createSuluAttributes(?string $resourceLocator, ?string $resourceLocatorPrefix): RequestAttributes
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getWebspaceKey()->willReturn('the_site');

        return new RequestAttributes([
            'portalInformation' => $portalInformation->reveal(),
            'matchType' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
            'resourceLocator' => $resourceLocator,
            'resourceLocatorPrefix' => $resourceLocatorPrefix,
        ]);
    }

    public function testGetRouteCollectionForRequestMatch(): void
    {
        $request = Request::create('/test');
        $request->attributes->set(RequestAttributeEnum::WEBSPACE->value, 'the_site');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, '/test');

        $routeModel = new Route('resource_key_example', '1', 'en', '/test', 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $this->routeRepository->findFirstBy(Argument::cetera())->willReturn($routeModel);
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routeCollection);
        $route = $routeCollection->get('sulu_route.route_id_1');

        $this->assertNotNull($route);

        $this->assertSame(
            [
                '_controller' => 'example.controller',
                '_sulu_route' => $routeModel,
            ],
            $route->getDefaults(),
        );
    }

    #[DataProvider('provideEncodedSlugMatches')]
    public function testGetRouteCollectionForRequestMatchDecodesEncodedSlug(string $encodedSlug, string $decodedSlug): void
    {
        $request = Request::create($encodedSlug);
        $request->attributes->set(RequestAttributeEnum::WEBSPACE->value, 'the_site');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, $encodedSlug);

        $routeModel = new Route('resource_key_example', '1', 'en', $decodedSlug, 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $this->routeRepository->findFirstBy([
            'webspaceOrNull' => 'the_site',
            'locale' => 'en',
            'slug' => $decodedSlug,
        ], ['webspace' => 'desc'])->willReturn($routeModel);

        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routeCollection);
        $route = $routeCollection->get('sulu_route.route_id_1');

        $this->assertNotNull($route);
        $this->assertSame(
            [
                '_controller' => 'example.controller',
                '_sulu_route' => $routeModel,
            ],
            $route->getDefaults(),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideEncodedSlugMatches(): array
    {
        return [
            'umlaut' => ['/design-system-k%C3%B6nnen', '/design-system-können'],
            'cjk' => ['/%E4%BD%A0%E5%A5%BD', '/你好'],
            'emoji' => ['/%F0%9F%98%80', '/😀'],
        ];
    }
}
