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

use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanupInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

final readonly class ResourceLocatorGenerator implements ResourceLocatorGeneratorInterface
{
    /**
     * @internal get the service always from the Service Container and never instantiate it directly
     */
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private PathCleanupInterface $pathCleanup,
    ) {
    }

    public function generate(ResourceLocatorRequest $request): string
    {
        $parentPath = '/';
        if ($request->parentResourceId) {
            $parentRoute = $this->routeRepository->findOneBy([
                'resourceKey' => $request->parentResourceKey,
                'resourceId' => $request->parentResourceId,
                'locale' => $request->locale,
            ]);

            $parentPath = $parentRoute?->getSlug() ?: '/';
        }

        $parts = \array_map(fn ($part) => $this->pathCleanup->cleanup($part, $request->locale), $request->parts);

        $path = '/' . \implode('-', $parts);

        // TODO routeSchema

        return $this->createUnique( // TODO own service called during doctrine listener also?
            \rtrim($parentPath, '/') . $path,
            $request->locale,
            $request->site,
            $request->resourceKey,
            $request->resourceId,
        );
    }

    private function createUnique(
        string $path,
        string $locale,
        ?string $site,
        string $resourceKey,
        ?string $resourceId,
    ): string {
        $originalPath = $path;
        $i = 0;

        while ($this->routeRepository->existBy(
            $resourceId ? [
                'locale' => $locale,
                'site' => $site,
                'slug' => $path,
                'excludeResource' => [
                    'resourceKey' => $resourceKey,
                    'resourceId' => $resourceId,
                ],
            ] : [
                'locale' => $locale,
                'site' => $site,
                'slug' => $path,
            ],
        )) {
            $path = $originalPath . '-' . (++$i);
        }

        return $path;
    }
}
