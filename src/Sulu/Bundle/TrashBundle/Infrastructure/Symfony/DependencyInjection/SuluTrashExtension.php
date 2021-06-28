<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Infrastructure\Symfony\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SuluTrashExtension extends Extension implements PrependExtensionInterface
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
                            'SuluTrashBundle' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../../../Resources/config/doctrine',
                                'prefix' => 'Sulu\Bundle\TrashBundle\Domain\Model',
                                'alias' => 'SuluTrashBundle',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../../../Resources/config/lists',
                        ],
                    ],
                    'resources' => [
                        TrashItemInterface::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_trash.get_trash-items',
                                'detail' => 'sulu_trash.get_trash-item',
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
        $container->setParameter('sulu_trash.storage.adapter', $storageAdapter);

        // set sulu_trash.trash_item_repository service based on configured storage adapter
        $trashItemRepositoryService = $storageAdapter
            ? 'sulu_trash.trash_item_repository.' . $storageAdapter
            : 'sulu_trash.trash_item_repository.null';
        $container->setAlias('sulu_trash.trash_item_repository', $trashItemRepositoryService);
        $container->setAlias(TrashItemRepositoryInterface::class, 'sulu_trash.trash_item_repository');

        $container->setParameter('sulu_trash.restore_form_mapping', $config['restore_form']);

        $container->registerForAutoconfiguration(StoreTrashItemHandlerInterface::class)
            ->addTag('sulu_trash.store_trash_item_handler');

        $container->registerForAutoconfiguration(RestoreTrashItemHandlerInterface::class)
            ->addTag('sulu_trash.restore_trash_item_handler');
    }
}
