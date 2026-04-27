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

use Sulu\Bundle\HttpCacheBundle\EventSubscriber\TagsSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_http_cache.event_subscriber.tags', TagsSubscriber::class)
        ->args([
            new Reference('sulu_website.reference_store_pool'),
            new Reference('fos_http_cache.http.symfony_response_tagger'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('kernel.event_subscriber');
};
