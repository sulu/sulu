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

use Sulu\Bundle\MediaBundle\Tests\Functional\Mock\InMemoryRemoteStorage;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Stores the media in memory with the flysystem 3.x storage, configured like in Sulu 3.0.
 */
class FlysystemV3Kernel extends Kernel implements CompilerPassInterface
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config_flysystem_v3.yml');
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . \DIRECTORY_SEPARATOR
            . 'var' . \DIRECTORY_SEPARATOR
            . 'cache' . \DIRECTORY_SEPARATOR
            . $this->getContext() . '_flysystem_v3' . \DIRECTORY_SEPARATOR
            . $this->environment;
    }

    public function process(ContainerBuilder $container): void
    {
        // the in-memory adapter is a local one, the media is served from the storage as with an S3 bucket
        $container->getDefinition('sulu_media.storage')->setClass(InMemoryRemoteStorage::class);
    }
}
