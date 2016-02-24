<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluResourceExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->setDefaultForFilterConditionsConjunction($config);
        $container->setParameter(
            'sulu_resource.filters.conjunction',
            $config['filters']['conjunctions']
        );

        $container->setParameter('sulu_resource.filters.contexts', $config['contexts']);

        $container->setParameter(
            'sulu_resource.list_builder.metadata.provider.filter.cache_dir',
            $this->createOrGetFolder('%sulu.cache_dir%/list-builder/filter', $container)
        );
    }

    /**
     * Create and return directory.
     *
     * @param string $directory
     * @param ContainerBuilder $container
     *
     * @return string
     */
    private function createOrGetFolder($directory, ContainerBuilder $container)
    {
        $filesystem = new Filesystem();

        $directory = $container->getParameterBag()->resolveValue($directory);
        if (!file_exists($directory)) {
            $filesystem->mkdir($directory);
        }

        return $directory;
    }

    /**
     * Sets default values for filter condition conjunction.
     *
     * @param $config
     */
    private function setDefaultForFilterConditionsConjunction(&$config)
    {
        if (!array_key_exists('filters', $config) ||
            !array_key_exists('conjunctions', $config['filters']) ||
            count($config['filters']['conjunctions']) === 0
        ) {
            $config['filters'] = [];
            $config['filters']['conjunctions'] = [
                [
                    'id' => 'and',
                    'translation' => 'resource.filter.conjunction.and',
                ],
                [
                    'id' => 'or',
                    'translation' => 'resource.filter.conjunction.or',
                ],
            ];
        }
    }
}
