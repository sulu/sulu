<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Application;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Memory\MemoryAdapter;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Tests\Functional\Mock\InMemoryRemoteStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Stores the media in memory behind a storage of the type remote, with flysystem 1.x and 3.x and without an adapter.
 */
class RemoteStorageKernel extends Kernel implements CompilerPassInterface
{
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'cache' . \DIRECTORY_SEPARATOR
            . $this->getContext() . '_remote_storage' . \DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function process(ContainerBuilder $container): void
    {
        $adapter = FlysystemVersion::isV3() ? InMemoryFilesystemAdapter::class : MemoryAdapter::class;

        $container->register('sulu_media_test.filesystem', Filesystem::class)
            ->setArguments([new Definition($adapter)])
            ->setPublic(true);
        $container->register('sulu_media_test.storage', InMemoryRemoteStorage::class)
            ->setArguments([new Reference('sulu_media_test.filesystem'), 10])
            ->setPublic(true);

        $container->setAlias('sulu_media.storage', 'sulu_media_test.storage')->setPublic(true);
        $container->setAlias(StorageInterface::class, 'sulu_media.storage')->setPublic(true);
    }
}
