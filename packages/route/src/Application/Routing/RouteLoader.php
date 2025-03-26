<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Routing;

use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * The RouteLoader requires that a previous request listener has set the site and slug attributes. In case of Sulu
 * this is done inside the PageBundle via a WebspaceRequestListener.
 */
final class RouteLoader implements RouteCollectionForRequestLoaderInterface
{
    public const REQUEST_ATTRIBUTE_SITE = 'site';

    public const REQUEST_ATTRIBUTE_SLUG = 'slug';

    public function __construct(private RouteRepositoryInterface $routeRepository)
    {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $locale = $request->getLocale();
        $site = $request->attributes->get(self::REQUEST_ATTRIBUTE_SITE);
        $slug = $request->attributes->get(self::REQUEST_ATTRIBUTE_SLUG);
        $routeCollection = new RouteCollection();

        if ((null !== $site && !\is_string($site))
            || null !== $slug
            || !\is_string($slug)
        ) {
            return $routeCollection;
        }

        $route = $this->routeRepository->findOneBy([
            'site' => $site,
            'locale' => $locale,
            'slug' => $slug,
        ]);

        // TODO: Implement getRouteCollectionForRequest() method.
    }
}
