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

trait PersistenceExtensionTrait
{

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function configure(array $config, ContainerBuilder $container)
    {

        $classes = isset($config['classes']) ? $config['classes'] : array();

        $configClasses = array('sulu' => $classes);

        $this->mapClassParameters($classes, $container);

        if ($container->hasParameter('sulu.persistence.classes')) {
            $configClasses = array_merge_recursive(
                $configClasses,
                $container->getParameter('sulu.persistence.classes')
            );
        }

        $container->setParameter('sulu.persistence.classes', $configClasses);
    }

    /**
     * Remap class parameters.
     *
     * @param array $classes
     * @param ContainerBuilder $container
     */
    protected function mapClassParameters(array $classes, ContainerBuilder $container)
    {
        foreach ($classes as $model => $serviceClasses) {
            foreach ($serviceClasses as $service => $class) {
                $container->setParameter(
                    sprintf(
                        'sulu.%s.%s.class',
                        $service,
                        $model
                    ),
                    $class
                );
            }
        }
    }
}
