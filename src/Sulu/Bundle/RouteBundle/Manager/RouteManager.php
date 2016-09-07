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
use Sulu\Bundle\RouteBundle\Exception\MissingClassMappingConfigurationException;
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
    private $routeGenerators;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @param RouteGeneratorInterface[] $routeGenerators
     * @param RouteRepositoryInterface $routeRepository
     * @param ConflictResolverInterface $conflictResolver
     * @param array $mappings
     */
    public function __construct(
        array $routeGenerators,
        RouteRepositoryInterface $routeRepository,
        ConflictResolverInterface $conflictResolver,
        array $mappings
    ) {
        $this->routeGenerators = $routeGenerators;
        $this->routeRepository = $routeRepository;
        $this->conflictResolver = $conflictResolver;
        $this->mappings = $mappings;
    }

    /**
     * {@inheritdoc}
     */
    public function create(RoutableInterface $entity, $path = null)
    {
        if (null !== $entity->getRoute()) {
            throw new RouteAlreadyCreatedException($entity);
        }

        $config = $this->getClassMappingConfiguration(get_class($entity));

        if (null === $path) {
            $path = $this->routeGenerators[$config['generator']]->generate($entity, $config['options']);
        }

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
     * Get class mapping configuration by class name or inheritance chain.
     *
     * @param string $className
     *
     * @return array
     *
     * @throws MissingClassMappingConfigurationException
     */
    protected function getClassMappingConfiguration($className)
    {
        if (array_key_exists($className, $this->mappings)) {
            return $this->mappings[$className];
        }

        $reflection = new \ReflectionClass($className);
        while ($reflection = $reflection->getParentClass()) {
            if (array_key_exists($reflection->getName(), $this->mappings)) {
                return $this->mappings[$reflection->getName()];
            }
        }

        throw new MissingClassMappingConfigurationException($className, array_keys($this->mappings));
    }

    /**
     * {@inheritdoc}
     */
    public function update(RoutableInterface $entity, $path = null)
    {
        if (null === $entity->getRoute()) {
            throw new RouteNotCreatedException($entity);
        }

        $config = $this->getClassMappingConfiguration(get_class($entity));

        if (null === $path) {
            $path = $this->routeGenerators[$config['generator']]->generate($entity, $config['options']);
        }

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
