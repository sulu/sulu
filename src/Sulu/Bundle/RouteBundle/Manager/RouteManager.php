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
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorPoolInterface;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;

/**
 * Manages routes.
 */
class RouteManager implements RouteManagerInterface
{
    /**
     * @var RouteGeneratorPoolInterface
     */
    private $routeGeneratorPool;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @param RouteGeneratorPoolInterface $routeGenerator
     * @param RouteRepositoryInterface $routeRepository
     * @param ConflictResolverInterface $conflictResolver
     *
     * @internal param RouteGeneratorInterface[] $routeGenerators
     * @internal param array $mappings
     */
    public function __construct(
        RouteGeneratorPoolInterface $routeGenerator,
        RouteRepositoryInterface $routeRepository,
        ConflictResolverInterface $conflictResolver
    ) {
        $this->routeGeneratorPool = $routeGenerator;
        $this->routeRepository = $routeRepository;
        $this->conflictResolver = $conflictResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function create(RoutableInterface $entity, $path = null)
    {
        if (null !== $entity->getRoute()) {
            throw new RouteAlreadyCreatedException($entity);
        }

        $generatedRoute = $this->routeGeneratorPool->generate($entity, $path);
        $path = $generatedRoute->getPath();

        $route = $this->routeRepository->createNew()
            ->setPath($path)
            ->setEntityClass($generatedRoute->getEntityClass())
            ->setEntityId($entity->getId())
            ->setLocale($entity->getLocale());

        $route = $this->conflictResolver->resolve($route);
        $entity->setRoute($route);

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function update(RoutableInterface $entity, $path = null)
    {
        if (null === $entity->getRoute()) {
            throw new RouteNotCreatedException($entity);
        }

        $generatedRoute = $this->routeGeneratorPool->generate($entity, $path);
        $path = $generatedRoute->getPath();

        if ($path === $entity->getRoute()->getPath()) {
            return $entity->getRoute();
        }

        $route = $this->routeRepository->createNew()
            ->setPath($path)->setEntityClass($generatedRoute->getEntityClass())
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
