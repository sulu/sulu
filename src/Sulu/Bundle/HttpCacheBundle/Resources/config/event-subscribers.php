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

use Sulu\Bundle\HttpCacheBundle\EventSubscriber\InvalidationSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_http_cache.event_subscriber.invalidation', InvalidationSubscriber::class)
        ->args([
            new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('request_stack'),
            new Reference('sulu_tag.tag_manager'),
            '%kernel.environment%',
        ])
        ->tag('sulu_document_manager.event_subscriber');
};
