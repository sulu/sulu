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

use Sulu\Component\Persistence\EventSubscriber\ORM\MetadataSubscriber;
use Sulu\Component\Persistence\EventSubscriber\ORM\TimestampableSubscriber;
use Sulu\Component\Persistence\EventSubscriber\ORM\UserBlameSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu.persistence.event_subscriber.orm.timestampable', TimestampableSubscriber::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata'])
        ->tag('doctrine.event_listener', ['event' => 'preUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'prePersist']);

    $services->set('sulu.persistence.event_subscriber.orm.user_blame', UserBlameSubscriber::class)
        ->args([new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        // Keep this priority lower than the metadata subscriber so metadata extensions are applied first.
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 50])
        // Keep the same priority for related flush handling to preserve listener ordering.
        ->tag('doctrine.event_listener', ['event' => 'onFlush', 'priority' => 50]);

    $services->set('sulu.persistence.event_subscriber.orm.metadata', MetadataSubscriber::class)
        ->args(['%sulu.persistence.objects%'])
        // Run very early so custom metadata is registered before other loadClassMetadata listeners depend on it.
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 8000]);
};
