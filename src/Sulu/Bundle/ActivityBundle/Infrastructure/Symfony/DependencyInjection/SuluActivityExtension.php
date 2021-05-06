<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Symfony\DependencyInjection;

use Sulu\Bundle\ActivityBundle\Domain\Repository\ActivityRepositoryInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SuluActivityExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluActivityBundle' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../../../Resources/config/doctrine',
                                'prefix' => 'Sulu\Bundle\ActivityBundle\Domain\Model',
                                'alias' => 'SuluActivityBundle',
                            ],
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);

        $storageAdapter = $container->resolveEnvPlaceholders($config['storage']['adapter'], true);
        $container->setParameter('sulu_activity.storage.adapter', $storageAdapter);

        $storagePersistPayload = $container->resolveEnvPlaceholders($config['storage']['persist_payload'], true);
        $container->setParameter('sulu_activity.storage.persist_payload', $storagePersistPayload);

        // set sulu_activity.activity_repository service based on configured storage adapter
        $activityRepositoryService = $storageAdapter
            ? 'sulu_activity.activity_repository.' . $storageAdapter
            : 'sulu_activity.activity_repository.null';
        $container->setAlias('sulu_activity.activity_repository', $activityRepositoryService);
        $container->setAlias(ActivityRepositoryInterface::class, 'sulu_activity.activity_repository');
    }
}
