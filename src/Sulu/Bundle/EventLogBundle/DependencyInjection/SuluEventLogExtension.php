<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\DependencyInjection;

use Sulu\Bundle\EventLogBundle\Entity\EventRecordRepositoryInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SuluEventLogExtension extends Extension
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);
        $container->setAlias('sulu_event_log.event_record_repository.doctrine', 'sulu.repository.event_record');

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
