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

namespace Sulu\Content\Tests\Application;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel for testing cache tags functionality specifically.
 * This kernel enables HTTP cache tags configuration.
 */
class CacheTagsKernel extends Kernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(function(ContainerBuilder $container) {
            $container->loadFromExtension('sulu_http_cache', [
                'proxy_client' => [
                    'noop' => true,
                ],
                'tags' => [
                    'enabled' => true,
                ],
            ]);
        });
    }
}
