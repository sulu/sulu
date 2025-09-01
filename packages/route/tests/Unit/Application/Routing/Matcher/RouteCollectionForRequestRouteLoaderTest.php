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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
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

    public function testGetRouteCollectionForRequestIncorrectSite(): void
    {
        $request = Request::create('/en/test');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, new \stdClass());

        $this->routeRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestNoSlug(): void
    {
        $request = Request::create('/en/test');
        $request->attributes->set(RequestAttributeEnum::SITE->value, 'the_site');

        $this->routeRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestNoRoute(): void
    {
        $request = Request::create('/test');
        $request->attributes->set(RequestAttributeEnum::SITE->value, 'the_site');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, '/test');

        $this->routeRepository->findOneBy(Argument::any())->willReturn(null);
        $routeCollection = $this->routeCollectionForRequestRouteLoader->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routeCollection);
    }

    public function testGetRouteCollectionForRequestMatch(): void
    {
        $request = Request::create('/test');
        $request->attributes->set(RequestAttributeEnum::SITE->value, 'the_site');
        $request->attributes->set(RequestAttributeEnum::SLUG->value, '/test');

        $routeModel = new Route('resource_key_example', '1', 'en', '/test', 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $this->routeRepository->findOneBy(Argument::any())->willReturn($routeModel);
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
}
