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

use Sulu\Component\Cache\Memoize;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.cache.memoize.cache_adapter', ArrayAdapter::class);

    $services->set('sulu_core.cache.memoize', Memoize::class)
        ->args([
            new Reference('sulu_core.cache.memoize.cache_adapter'),
            '%sulu_core.cache.memoize.default_lifetime%',
        ])
        ->tag('kernel.reset', ['method' => 'reset']);
};
