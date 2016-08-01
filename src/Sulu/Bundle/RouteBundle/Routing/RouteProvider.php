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
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides symfony-routes by request or name.
 */
class RouteProvider implements RouteProviderInterface
{
    const ROUTE_PREFIX = 'sulu_route_';

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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RouteRepositoryInterface $routeRepository
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param RouteDefaultsProviderInterface $routeDefaultsProvider
     * @param RequestStack $requestStack
     */
    public function __construct(
        RouteRepositoryInterface $routeRepository,
        RequestAnalyzerInterface $requestAnalyzer,
        RouteDefaultsProviderInterface $routeDefaultsProvider,
        RequestStack $requestStack
    ) {
        $this->routeRepository = $routeRepository;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->routeDefaultsProvider = $routeDefaultsProvider;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();
        $path = $request->getPathInfo();
        $prefix = $this->requestAnalyzer->getResourceLocatorPrefix();

        if (!empty($prefix) && strpos($path, $prefix) === 0) {
            $path = PathHelper::relativizePath($path, $prefix);
        }

        $route = $this->routeRepository->findByPath('/' . ltrim($path, '/'), $request->getLocale());

        if (!$route
            || !$this->routeDefaultsProvider->supports($route->getEntityClass())
            || !$this->routeDefaultsProvider->isPublished(
                $route->getEntityClass(),
                $route->getEntityId(),
                $route->getLocale()
            )
        ) {
            return $collection;
        }

        $collection->add(
            self::ROUTE_PREFIX . $route->getId(),
            $this->createRoute($route, $request)
        );

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name)
    {
        if (strpos($name, self::ROUTE_PREFIX) !== 0) {
            throw new RouteNotFoundException();
        }

        $routeId = substr($name, strlen(self::ROUTE_PREFIX));

        /** @var RouteInterface $route */
        $route = $this->routeRepository->find($routeId);

        if (!$route) {
            throw new RouteNotFoundException();
        }

        return $this->createRoute($route, $this->requestStack->getCurrentRequest());
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names)
    {
        return [];
    }

    /**
     * Will create a symfony route.
     *
     * @param RouteInterface $route
     * @param Request $request
     *
     * @return Route
     */
    protected function createRoute(RouteInterface $route, Request $request)
    {
        $routePath = $this->requestAnalyzer->getResourceLocatorPrefix() . $route->getPath();

        if ($route->isHistory()) {
            return new Route(
                $routePath,
                [
                    '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
                    'url' => $request->getSchemeAndHttpHost()
                        . $this->requestAnalyzer->getResourceLocatorPrefix()
                        . $route->getTarget()->getPath()
                        . ($request->getQueryString() ? ('?' . $request->getQueryString()) : ''),
                ]
            );
        }

        return new Route(
            $routePath,
            $this->routeDefaultsProvider->getByEntity(
                $route->getEntityClass(),
                $route->getEntityId(),
                $request->getLocale()
            )
        );
    }
}
