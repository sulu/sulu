<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Resolves given target entities (interfaces) with container parameters.
 */
class ResolveTargetEntitiesPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $interfaces;

    public function __construct(array $interfaces)
    {
        $this->interfaces = $interfaces;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->resolve($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function resolve(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
            throw new \RuntimeException('Cannot find Doctrine Target Entity Resolver Listener.');
        }

        $resolveTargetEntityListener = $container->findDefinition('doctrine.orm.listeners.resolve_target_entity');

        $interfaceMapping = [];
        foreach ($this->interfaces as $interface => $model) {
            $interfaceImplementation = $this->getClass($container, $model);
            $interfaceMapping[$interface] = $interfaceImplementation;

            $resolveTargetEntityListener
                ->addMethodCall('addResolveTargetEntity', [$interface, $interfaceImplementation, []]);
        }

        // update $targetEntityMapping argument of ReferencesOption service
        // this is needed to allow for using interfaces when using a "references" option in a doctrine schema
        $doctrineReference = $container->findDefinition('sulu_core.doctrine.references');
        $oldTargetEntityMapping = $doctrineReference->getArgument('$targetEntityMapping');
        $doctrineReference->setArgument('$targetEntityMapping', array_merge($oldTargetEntityMapping, $interfaceMapping));
    }

    /**
     * @param ContainerBuilder $container
     * @param string $key
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function getClass(ContainerBuilder $container, $key)
    {
        if ($container->hasParameter($key)) {
            return $container->getParameter($key);
        }

        if (class_exists($key)) {
            return $key;
        }

        throw new \InvalidArgumentException(
            sprintf('The class %s does not exist.', $key)
        );
    }
}
