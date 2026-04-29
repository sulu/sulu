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

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_http_cache.cache_lifetime.resolver', CacheLifetimeResolver::class);

    $services->set('sulu_http_cache.reference_store', ReferenceStore::class);

    $services->alias(ReferenceStoreInterface::class, 'sulu_http_cache.reference_store');
};
