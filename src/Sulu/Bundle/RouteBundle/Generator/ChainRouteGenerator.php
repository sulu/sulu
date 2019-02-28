<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Exception\MissingClassMappingConfigurationException;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;

/**
 * Using route-generators and configuration to generate route.
 */
class ChainRouteGenerator implements ChainRouteGeneratorInterface
{
    /**
     * @var array
     */
    private $mappings;

    /**
     * @var RouteGeneratorInterface[]
     */
    private $routeGenerators;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @param array $mappings
     * @param RouteGeneratorInterface[] $routeGenerators
     * @param RouteRepositoryInterface $routeRepository
     */
    public function __construct(array $mappings, array $routeGenerators, RouteRepositoryInterface $routeRepository)
    {
        $this->mappings = $mappings;
        $this->routeGenerators = $routeGenerators;
        $this->routeRepository = $routeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(RoutableInterface $entity, $path = null)
    {
        $config = $this->getClassMappingConfiguration(get_class($entity));

        if (!$path) {
            $generator = $this->routeGenerators[$config['mapping']['generator']];
            $path = $generator->generate($entity, $config['mapping']['options']);
        }

        return $this->routeRepository->createNew()
            ->setEntityClass($config['className'])
            ->setEntityId($entity->getId())
            ->setLocale($entity->getLocale())
            ->setPath($path);
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
            return [
                'className' => $className,
                'mapping' => $this->mappings[$className],
            ];
        }

        $reflection = new \ReflectionClass($className);
        while ($reflection = $reflection->getParentClass()) {
            if (array_key_exists($reflection->getName(), $this->mappings)) {
                return [
                    'className' => $reflection->getName(),
                    'mapping' => $this->mappings[$reflection->getName()],
                ];
            }
        }

        throw new MissingClassMappingConfigurationException($className, array_keys($this->mappings));
    }
}
