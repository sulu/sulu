<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\ResourceLocator;

use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

final readonly class ResourceLocatorGenerator implements ResourceLocatorGeneratorInterface
{
    private const DEFAULT_ROUTE_SCHEMA = '/{implode("-", object)}';

    /**
     * @internal get the service always from the Service Container and never instantiate it directly
     */
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private RouteSchemaEvaluatorInterface $routeSchemaEvaluator,
    ) {
    }

    public function generate(ResourceLocatorRequest $request): string
    {
        $parentPath = $this->resolveParentPath($request);

        if (null === $request->routeSchema) {
            $request = $request->withRouteSchema(self::DEFAULT_ROUTE_SCHEMA);
        }

        $path = $this->routeSchemaEvaluator->evaluate($request);

        $uniquePath = $this->createUnique(
            $parentPath . $path,
            $request->locale,
            $request->webspace,
            $request->resourceKey,
            $request->resourceId,
        );

        if ($request->relative) {
            return \substr($uniquePath, \strlen($parentPath));
        }

        return $uniquePath;
    }

    private function resolveParentPath(ResourceLocatorRequest $request): string
    {
        if (!$request->parentResourceId) {
            return '';
        }

        $parentRoute = $this->routeRepository->findOneBy([
            'resourceKey' => $request->parentResourceKey,
            'resourceId' => $request->parentResourceId,
            'locale' => $request->locale,
        ]);

        return \rtrim($parentRoute?->getSlug() ?? '', '/');
    }

    private function createUnique(
        string $path,
        string $locale,
        ?string $webspace,
        string $resourceKey,
        ?string $resourceId,
    ): string {
        $originalPath = $path;
        $i = 0;

        while ($this->routeRepository->existBy(
            $resourceId ? [
                'locale' => $locale,
                'webspace' => $webspace,
                'slug' => $path,
                'excludeResource' => [
                    'resourceKey' => $resourceKey,
                    'resourceId' => $resourceId,
                ],
            ] : [
                'locale' => $locale,
                'webspace' => $webspace,
                'slug' => $path,
            ],
        )) {
            $path = $originalPath . '-' . (++$i);
        }

        return $path;
    }
}
