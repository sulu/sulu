<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Routing;

use PHPCR\Util\PathHelper;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides symfony-routes by request or name.
 */
class RouteProvider implements RouteProviderInterface
{
    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouteDefaultsProviderInterface
     */
    private $routeDefaultsProvider;

    /**
     * @param RouteRepositoryInterface $routeRepository
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param RouteDefaultsProviderInterface $routeDefaultsProvider
     */
    public function __construct(
        RouteRepositoryInterface $routeRepository,
        RequestAnalyzerInterface $requestAnalyzer,
        RouteDefaultsProviderInterface $routeDefaultsProvider
    ) {
        $this->routeRepository = $routeRepository;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->routeDefaultsProvider = $routeDefaultsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();
        $path = $request->getPathInfo();
        $prefix = $this->requestAnalyzer->getResourceLocatorPrefix();
        if (!empty($prefix)) {
            $path = PathHelper::relativizePath($path, $prefix);
        }

        $route = $this->routeRepository->findByPath('/' . ltrim($path, '/'), $request->getLocale());
        if (!$route || !$this->routeDefaultsProvider->supports($route->getEntityClass())) {
            return $collection;
        }

        if ($route->isHistory()) {
            $collection->add(
                uniqid('sulu_history_route_', true),
                new Route(
                    $request->getPathInfo(),
                    [
                        '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
                        'url' => $request->getSchemeAndHttpHost()
                            . $this->requestAnalyzer->getResourceLocatorPrefix()
                            . $route->getTarget()->getPath()
                            . ($request->getQueryString() ? ('?' . $request->getQueryString()) : ''),
                    ]
                )
            );

            return $collection;
        }

        // TODO move route-name to entity

        $collection->add(
            uniqid('sulu_route_', true),
            new Route(
                $request->getPathInfo(),
                $this->routeDefaultsProvider->getByEntity($route->getEntityClass(), $route->getEntityId())
            )
        );

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name)
    {
        throw new RouteNotFoundException();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names)
    {
        return [];
    }
}
