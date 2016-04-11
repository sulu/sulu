<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Define repository services for each object (e.g. sulu.repository.[object name]) and
 * map all object parameters to the container.
 */
trait PersistenceExtensionTrait
{
    /**
     * @param array            $objects
     * @param ContainerBuilder $container
     */
    protected function configurePersistence(array $objects, ContainerBuilder $container)
    {
        $this->defineRepositories($objects, $container);

        $this->remapObjectParameters($objects, $container);

        $configObjects = ['sulu' => $objects];

        if ($container->hasParameter('sulu.persistence.objects')) {
            $configObjects = array_merge_recursive(
                $configObjects,
                $container->getParameter('sulu.persistence.objects')
            );
        }

        $container->setParameter('sulu.persistence.objects', $configObjects);
    }

    /**
     * Define repositories.
     *
     * @param array            $objects
     * @param ContainerBuilder $container
     */
    private function defineRepositories(array $objects, ContainerBuilder $container)
    {
        foreach ($objects as $object => $services) {
            if (array_key_exists('model', $services)) {
                $repositoryDefinition = $this->getRepositoryDefinition($object, $services, $container);

                $container->setDefinition(
                    $this->getContainerKey('repository', $object),
                    $repositoryDefinition
                );
            }
        }
    }

    /**
     * Get the repository service definition.
     *
     * @param string           $object
     * @param array            $services
     * @param ContainerBuilder $container
     *
     * @return Definition
     */
    private function getRepositoryDefinition($object, array $services, ContainerBuilder $container)
    {
        $repositoryKey = $this->getContainerKey('repository', $object, '.class');

        // default repository
        $repositoryClass = 'Sulu\Component\Persistence\Repository\ORM\EntityRepository';

        if ($container->hasParameter($repositoryKey)) {
            $repositoryClass = $container->getParameter($repositoryKey);
        }

        if (isset($services['repository'])) {
            $repositoryClass = $services['repository'];
        }

        $definition = new Definition($repositoryClass);
        $definition->setArguments([
            new Reference($this->getEntityManagerServiceKey()),
            $this->getClassMetadataDefinition($services['model']),
        ]);

        return $definition;
    }

    /**
     * @param mixed $model
     *
     * @return Definition
     */
    private function getClassMetadataDefinition($model)
    {
        $definition = new Definition('Doctrine\ORM\Mapping\ClassMetadata');
        $definition
            ->setFactory([
                new Reference($this->getEntityManagerServiceKey()),
                'getClassMetadata',
            ])
            ->setArguments([$model])
            ->setPublic(false);

        return $definition;
    }

    /**
     * Remap object parameters.
     *
     * @param array            $objects
     * @param ContainerBuilder $container
     */
    private function remapObjectParameters(array $objects, ContainerBuilder $container)
    {
        foreach ($objects as $object => $services) {
            foreach ($services as $service => $class) {
                $container->setParameter(
                    sprintf(
                        'sulu.%s.%s.class',
                        $service,
                        $object
                    ),
                    $class
                );
            }
        }
    }

    /**
     * Get container key.
     *
     * @param string      $key
     * @param string      $object
     * @param string|null $suffix
     *
     * @return string
     */
    private function getContainerKey($key, $object, $suffix = null)
    {
        return sprintf('sulu.%s.%s%s', $key, $object, $suffix);
    }

    /**
     * Get the entity manager.
     *
     * @return string
     */
    private function getEntityManagerServiceKey()
    {
        return 'doctrine.orm.default_entity_manager';
    }
}
