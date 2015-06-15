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

trait PersistenceExtensionTrait
{

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function configure(array $config, ContainerBuilder $container)
    {

        $objects = isset($config['objects']) ? $config['objects'] : array();

        $this->defineRepositories($objects, $container);

        $this->remapObjectParameters($objects, $container);

        $configObjects = array('sulu' => $objects);

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
     * @param array $objects
     * @param ContainerBuilder $container
     */
    protected function defineRepositories(array $objects, ContainerBuilder $container)
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
     * @param string $object
     * @param array $services
     * @param ContainerBuilder $container
     *
     * @return Definition
     */
    protected function getRepositoryDefinition($object, array $services, ContainerBuilder $container)
    {
        $repositoryKey = $this->getContainerKey('repository', $object, '.class');
        $repositoryClass = 'Sulu\Component\Persistence\Repository\ORM\EntityRepository';

        if ($container->hasParameter($repositoryKey)) {
            $repositoryClass = $container->getParameter($repositoryKey);
        }

        if (isset($services['repository'])) {
            $repositoryClass = $services['repository'];
        }

        $definition = new Definition($repositoryClass);
        $definition->setArguments(array(
            new Reference($this->getEntityManagerServiceKey()),
            $this->getClassMetadataDefinition($services['model']),
        ));

        return $definition;
    }

    /**
     * @param mixed $model
     *
     * @return Definition
     */
    protected function getClassMetadataDefinition($model)
    {
        $definition = new Definition('Doctrine\ORM\Mapping\ClassMetadata');
        $definition
            ->setFactory(array(
                new Reference($this->getEntityManagerServiceKey()),
                'getClassMetadata',
            ))
            ->setArguments(array($model))
            ->setPublic(false);

        return $definition;
    }

    /**
     * Remap object parameters.
     *
     * @param array $objects
     * @param ContainerBuilder $container
     */
    protected function remapObjectParameters(array $objects, ContainerBuilder $container)
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
     * @param string $key
     * @param string $object
     * @param string|null $suffix
     *
     * @return string
     */
    protected function getContainerKey($key, $object, $suffix = null)
    {
        return sprintf('sulu.%s.%s%s', $key, $object, $suffix);
    }

    /**
     * Get the entity manager.
     *
     * @return string
     */
    protected function getEntityManagerServiceKey()
    {
        return 'doctrine.orm.default_entity_manager';
    }
}
