<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\SymfonyCmf\Routing;

use Sulu\Route\Application\Routing\Matcher\RouteCollectionForRequestLoaderInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final readonly class CmfRouteProvider implements RouteProviderInterface
{
    /**
     * @param RouteCollectionForRequestLoaderInterface[] $routeCollectionForRequestLoaders
     * @param array<string, mixed> $routeDefaultsOptions
     */
    public function __construct(
        private iterable $routeCollectionForRequestLoaders,
        private array $routeDefaultsOptions,
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        foreach ($this->routeCollectionForRequestLoaders as $routeCollectionLoader) {
            $routeCollection = $routeCollectionLoader->getRouteCollectionForRequest($request);

            if (0 !== \count($routeCollection)) {
                $routeCollection->addOptions($this->routeDefaultsOptions);

                return $routeCollection;
            }
        }

        return new RouteCollection();
    }

    public function getRouteByName(string $name): SymfonyRoute
    {
        throw new RouteNotFoundException(
            \sprintf('Sulu CmfRouteProvider does not support getRouteByName("%s").', $name),
        );
    }

    public function getRoutesByNames(?array $names = null): iterable
    {
        return [];
    }
}
