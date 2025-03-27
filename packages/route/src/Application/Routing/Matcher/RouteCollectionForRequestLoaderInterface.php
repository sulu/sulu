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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

interface RouteCollectionForRequestLoaderInterface
{
    /**
     * Provide a RouteCollection which contains all routes matching the given request.
     *
     * @example
     *
     * ```php
     *      $routeCollection = new RouteCollection();
     *      $routeCollection->add('my_dynamic_route', new Symfony\Component\Routing\Route(
     *          $request->getPathInfo(),
     *          defaults: [
     *              '_controller' => MyController::class,
     *          ],
     *          host: $request->getHost(),
     *      ));
     *
     *      return $routeCollection;
     * ```
     */
    public function getRouteCollectionForRequest(Request $request): RouteCollection;
}
