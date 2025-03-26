<?php

namespace Sulu\Route\Application\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

interface RouteCollectionForRequestLoaderInterface
{
    public function getRouteCollectionForRequest(Request $request): RouteCollection;
}
