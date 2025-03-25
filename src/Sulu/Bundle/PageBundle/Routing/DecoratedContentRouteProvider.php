<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Routing;

use Sulu\Bundle\WebsiteBundle\Routing\ContentRouteProvider;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

// TODO remove this together with the old SuluPageBundle
class DecoratedContentRouteProvider implements RouteProviderInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ?ContentRouteProvider $inner = null,
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        if (!$this->inner) {
            throw new \RuntimeException('No inner route provider set');
        }

        // If the new SuluPageBundle is registered, we need to skip this route provider
        // @phpstan-ignore-next-line
        if (\array_key_exists('SuluNextPageBundle', $this->container->getParameter('kernel.bundles'))) {
            return new RouteCollection();
        }

        return $this->inner->getRouteCollectionForRequest($request);
    }

    public function getRouteByName(string $name): SymfonyRoute
    {
        if (!$this->inner) {
            throw new \RuntimeException('No inner route provider set');
        }

        return $this->inner->getRouteByName($name);
    }

    public function getRoutesByNames(?array $names = null): iterable
    {
        if (!$this->inner) {
            throw new \RuntimeException('No inner route provider set');
        }

        return $this->inner->getRoutesByNames($names);
    }
}
