<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Routing\Matcher;

use Psr\Container\ContainerInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal This is an internal class overwrite by decorate the service and use the `RouteCollectionForRequestLoaderInterface` not this class.
 *
 * The RouteLoader requires that a previous request listener has set the site and slug attributes. In case of Sulu
 * this is done inside the PageBundle via a WebspaceRequestListener.
 */
final readonly class RouteCollectionForRequestRouteLoader implements RouteCollectionForRequestLoaderInterface
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private ContainerInterface $routeDefaultsProviderLocator,
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $locale = $request->getLocale();
        $site = $request->attributes->get(RequestAttributeEnum::SITE->value);
        $slug = $request->attributes->get(RequestAttributeEnum::SLUG->value);

        if ((null !== $site && !\is_string($site))
            || !\is_string($slug)
        ) {
            return new RouteCollection();
        }

        $route = $this->routeRepository->findOneBy([
            'site' => $site,
            'locale' => $locale,
            'slug' => $slug,
        ]);

        if (null === $route) {
            return new RouteCollection();
        }

        $routeDefaultsProvider = $this->routeDefaultsProviderLocator->get($route->getResourceKey());
        \assert($routeDefaultsProvider instanceof RouteDefaultsProviderInterface, 'The RouteDefaultsProvider must implement RouteDefaultsProviderInterface but got: ' . \get_debug_type($routeDefaultsProvider));
        $defaults = $routeDefaultsProvider->getDefaults($route);
        $defaults['_sulu_route'] = $route;

        $routeCollection = new RouteCollection();
        $symfonyRoute = new SymfonyRoute(
            $request->getPathInfo(),
            $defaults,
            host: $request->getHost(),
        );

        $routeCollection->add('sulu_route.route_id_' . $route->getId(), $symfonyRoute);

        return $routeCollection;
    }
}
