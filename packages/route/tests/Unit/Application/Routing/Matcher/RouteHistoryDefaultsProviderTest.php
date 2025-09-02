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
use Sulu\Route\Application\Routing\Generator\RouteGenerator;
use Sulu\Route\Application\Routing\Generator\SiteRouteGeneratorInterface;
use Sulu\Route\Application\Routing\Matcher\RouteHistoryDefaultsProvider;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Translation\LocaleSwitcher;

#[CoversClass(RouteHistoryDefaultsProvider::class)]
class RouteHistoryDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    private RequestContext $requestContext;

    private RouteGenerator $routeGenerator;

    private RouteHistoryDefaultsProvider $routeHistoryDefaultsProvider;

    protected function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        // Use the same setup as in RouteGeneratorTest
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

        $this->requestContext = new RequestContext();
        $this->routeGenerator = new RouteGenerator($container, $this->requestContext, new RequestStack(), new LocaleSwitcher('en', [], $this->requestContext));

        $this->routeHistoryDefaultsProvider = new RouteHistoryDefaultsProvider(
            $this->routeRepository->reveal(),
            $this->routeGenerator
        );
    }

    public function testGetDefaultsFound(): void
    {
        $routeModel = new Route(Route::HISTORY_RESOURCE_KEY, 'resource_key_example::1', 'en', '/test', 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $targetRoute = new Route('resource_key_example', '1', 'en', '/test', 'the_site');
        static::setPrivateProperty($targetRoute, 'id', 2);

        $this->routeRepository->findOneBy(Argument::any())->willReturn($targetRoute);

        $this->assertSame(
            [
                '_controller' => RedirectController::class,
                'path' => '/en/test',
                'permanent' => true,
                '_sulu_route_target' => $targetRoute,
            ],
            $this->routeHistoryDefaultsProvider->getDefaults($routeModel),
        );
    }

    public function testGetDefaultsNotFound(): void
    {
        $this->expectException(GoneHttpException::class);

        $routeModel = new Route(Route::HISTORY_RESOURCE_KEY, 'resource_key_example::1', 'en', '/test', 'the_site');
        static::setPrivateProperty($routeModel, 'id', 1);

        $this->routeRepository->findOneBy(Argument::any())->willReturn(null);

        $this->routeHistoryDefaultsProvider->getDefaults($routeModel);
    }
}
