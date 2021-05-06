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

use Sulu\Bundle\ActivityBundle\Domain\Repository\EventRecordRepositoryInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SuluEventLogExtension extends Extension implements PrependExtensionInterface
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
                            'SuluEventLogBundle' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../../../Resources/config/doctrine',
                                'prefix' => 'Sulu\Bundle\ActivityBundle\Domain\Model',
                                'alias' => 'SuluEventLogBundle',
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
        $container->setParameter('sulu_event_log.storage.adapter', $storageAdapter);

        $storagePersistPayload = $container->resolveEnvPlaceholders($config['storage']['persist_payload'], true);
        $container->setParameter('sulu_event_log.storage.persist_payload', $storagePersistPayload);

        // set sulu_event_log.event_record_repository service based on configured storage adapter
        $eventRecordRepositoryService = $storageAdapter
            ? 'sulu_event_log.event_record_repository.' . $storageAdapter
            : 'sulu_event_log.event_record_repository.null';
        $container->setAlias('sulu_event_log.event_record_repository', $eventRecordRepositoryService);
        $container->setAlias(EventRecordRepositoryInterface::class, 'sulu_event_log.event_record_repository');
    }
}
