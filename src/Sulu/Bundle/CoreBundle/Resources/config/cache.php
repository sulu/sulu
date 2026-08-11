<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use ProxyManager\Configuration;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Sulu\Component\Cache\Memoize;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.cache.memoize.cache_adapter', ArrayAdapter::class);

    $services->set('sulu_core.cache.memoize.cache', CacheProvider::class)
        ->args([new Reference('sulu_core.cache.memoize.cache_adapter')])
        ->factory([DoctrineProvider::class, 'wrap']);

    $services->set('sulu_core.cache.memoize', Memoize::class)
        ->args([
            new Reference('sulu_core.cache.memoize.cache'),
            '%sulu_core.cache.memoize.default_lifetime%',
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_core.proxy_manager.configuration', Configuration::class)
        ->private()
        ->call('setProxiesTargetDir', ['%sulu_core.proxy_cache_dir%'])
        ->call('setGeneratorStrategy', [new Reference('sulu_core.proxy_manager.file_writer_generator_strategy')]);

    $services->set('sulu_core.proxy_manager.file_writer_generator_strategy', FileWriterGeneratorStrategy::class)
        ->private()
        ->args([new Reference('sulu_core.proxy_manager.file_locator')]);

    $services->set('sulu_core.proxy_manager.file_locator', FileLocator::class)
        ->private()
        ->args(['%sulu_core.proxy_cache_dir%']);
};
