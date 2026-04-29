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

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancer;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancerInterface;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_http_cache.cache_lifetime.enhancer', CacheLifetimeEnhancer::class)
        ->public()
        ->args([
            new Reference('sulu_http_cache.cache_lifetime.request_store'),
            '%sulu_http_cache.cache.max_age%',
            '%sulu_http_cache.cache.shared_max_age%',
        ]);

    $services->set('sulu_http_cache.cache_lifetime.request_store', CacheLifetimeRequestStore::class)
        ->args([new Reference('request_stack')]);

    $services->alias(CacheLifetimeEnhancerInterface::class, 'sulu_http_cache.cache_lifetime.enhancer');
};
