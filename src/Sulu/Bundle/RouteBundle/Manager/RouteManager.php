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
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;

/**
 * Manages routes.
 */
class RouteManager implements RouteManagerInterface
{
    /**
     * @var array
     */
    private $mappings;

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @param RouteGeneratorInterface   $routeGenerator
     * @param RouteRepositoryInterface  $routeRepository
     * @param ConflictResolverInterface $conflictResolver
     * @param array                     $mappings
     */
    public function __construct(
        RouteGeneratorInterface $routeGenerator,
        RouteRepositoryInterface $routeRepository,
        ConflictResolverInterface $conflictResolver,
        array $mappings
    ) {
        $this->routeGenerator = $routeGenerator;
        $this->routeRepository = $routeRepository;
        $this->conflictResolver = $conflictResolver;
        $this->mappings = $mappings;
    }

    /**
     * {@inheritdoc}
     */
    public function create(RoutableInterface $entity)
    {
        if (null !== $entity->getRoute()) {
            throw new RouteAlreadyCreatedException($entity);
        }

        $path = $this->routeGenerator->generate($entity, $this->mappings[get_class($entity)]['route_schema']);
        $route = $this->routeRepository->createNew()
            ->setPath($path)
            ->setEntityClass(get_class($entity))
            ->setEntityId($entity->getId())
            ->setLocale($entity->getLocale());

        $route = $this->conflictResolver->resolve($route);
        $entity->setRoute($route);

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function update(RoutableInterface $entity)
    {
        if (null === $entity->getRoute()) {
            throw new RouteNotCreatedException($entity);
        }

        $path = $this->routeGenerator->generate($entity, $this->mappings[get_class($entity)]['route_schema']);
        if ($path === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $route = $this->routeRepository->createNew()
            ->setPath($path)
            ->setEntityClass(get_class($entity))
            ->setEntityId($entity->getId())
            ->setLocale($entity->getLocale());
        $route = $this->conflictResolver->resolve($route);

        // path haven't changed after conflict resolving
        if ($route->getPath() === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $historyRoute = $entity->getRoute()
            ->setHistory(true)
            ->setTarget($route);
        $route->addHistory($historyRoute);

        foreach ($historyRoute->getHistories() as $historyRoute) {
            if ($historyRoute->getPath() === $route->getPath()) {
                // the history route will be restored
                $historyRoute->removeTarget()
                    ->setHistory(false);

                continue;
            }

            $route->addHistory($historyRoute);
            $historyRoute->setTarget($route);
        }

        $entity->setRoute($route);

        return $route;
    }
}
