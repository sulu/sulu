<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Manager;

use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\RouteIsNotUniqueException;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Manages routes.
 */
class RouteManager implements RouteManagerInterface
{
    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @param ChainRouteGeneratorInterface $chainRouteGenerator
     * @param ConflictResolverInterface $conflictResolver
     * @param RouteRepositoryInterface $routeRepository
     */
    public function __construct(
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver,
        RouteRepositoryInterface $routeRepository
    ) {
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->conflictResolver = $conflictResolver;
        $this->routeRepository = $routeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function create(RoutableInterface $entity, $path = null, $resolveConflict = true)
    {
        if (null !== $entity->getRoute()) {
            throw new RouteAlreadyCreatedException($entity);
        }

        $route = $this->chainRouteGenerator->generate($entity, $path);
        if ($resolveConflict) {
            $route = $this->conflictResolver->resolve($route);
        } elseif (!$this->isUnique($route)) {
            throw new RouteIsNotUniqueException($route, $entity);
        }

        $entity->setRoute($route);

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function update(RoutableInterface $entity, $path = null, $resolveConflict = true)
    {
        if (null === $entity->getRoute()) {
            throw new RouteNotCreatedException($entity);
        }

        $route = $this->chainRouteGenerator->generate($entity, $path);
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        if ($resolveConflict) {
            $route = $this->conflictResolver->resolve($route);
        } else {
            $route = $this->resolve($route, $entity);
        }

        // path haven't changed after conflict resolving
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $historyRoute = $entity->getRoute()->setHistory(true)->setTarget($route);
        $route->addHistory($historyRoute);

        foreach ($historyRoute->getHistories() as $historyRoute) {
            if ($historyRoute->getPath() === $route->getPath()) {
                // the history route will be restored
                $historyRoute->removeTarget()->setHistory(false);

                continue;
            }

            $route->addHistory($historyRoute);
            $historyRoute->setTarget($route);
        }

        $entity->setRoute($route);

        return $route;
    }

    /**
     * Returns true if route is unique.
     *
     * @param RouteInterface $route
     *
     * @return bool
     */
    private function isUnique(RouteInterface $route)
    {
        $persistedRoute = $this->routeRepository->findByPath($route->getPath(), $route->getLocale());

        return !$persistedRoute;
    }

    /**
     * Looks for the same route in the database.
     * If no route was found the method returns the newly created route.
     * If the route is a history route for given entity the history route will be returned.
     * Else a RouteIsNotUniqueException will be thrown.
     *
     * @param RouteInterface $route
     * @param RoutableInterface $entity
     *
     * @return RouteInterface
     *
     * @throws RouteIsNotUniqueException
     */
    private function resolve(RouteInterface $route, RoutableInterface $entity)
    {
        $persistedRoute = $this->routeRepository->findByPath($route->getPath(), $route->getLocale());

        if (!$persistedRoute) {
            return $route;
        }

        if ($persistedRoute->getEntityClass() === $route->getEntityClass()
            && $persistedRoute->getEntityId() === $route->getEntityId()
        ) {
            return $persistedRoute;
        }

        throw new RouteIsNotUniqueException($route, $entity);
    }
}
