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

use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * @internal This class is internal and should not be used or extended.
 *           If you want to overwrite the service create a new class implementing RouteDefaultsProviderInterface.
 *           And decorate the `sulu_route.route_history_defaults_provider` service.
 */
final readonly class RouteHistoryDefaultsProvider implements RouteDefaultsProviderInterface
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private RouteGeneratorInterface $routeGenerator,
    ) {
    }

    public function getDefaults(Route $route): array
    {
        \assert($route->isHistory(), \sprintf('Route must be of type "%s", but "%s" given.', Route::HISTORY_RESOURCE_KEY, $route->getResourceKey()));

        $resourceIdParts = \explode('::', $route->getResourceId(), 2);
        $resourceKey = $resourceIdParts[0];
        $resourceId = $resourceIdParts[1] ?? '';

        if ('' === $resourceKey || '' === $resourceId) {
            throw new \RuntimeException(\sprintf('The given history route "resourceId" has to contain resourceKey and resourceId separated by "::", but "%s" given.', $route->getResourceId()));
        }

        $targetRoute = $this->routeRepository->findOneBy([
            'resourceKey' => $resourceKey,
            'resourceId' => $resourceId,
            'locale' => $route->getLocale(),
        ]);

        if (null === $targetRoute) {
            throw new GoneHttpException(\sprintf('The target route with resourceKey "%s" and resourceId "%s" no longer exists.', $resourceKey, $resourceId));
        }

        $url = $this->routeGenerator->generate($targetRoute->getSlug(), $targetRoute->getLocale(), $targetRoute->getSite());

        return [
            '_controller' => RedirectController::class,
            'path' => $url,
            'permanent' => true,
            '_sulu_route_target' => $targetRoute, // TODO add a kernel.controller listener to the content bundle which validates if the target is published else throw 410 like above
        ];
    }

    public static function getResourceKey(): string
    {
        return Route::HISTORY_RESOURCE_KEY;
    }
}
