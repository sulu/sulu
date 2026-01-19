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
    $parameters = $container->parameters();
    $parameters->set('sulu.persistence.event_subscriber.orm.timestampable.class', TimestampableSubscriber::class);
    $parameters->set('sulu.persistence.event_subscriber.orm.user_blame.class', UserBlameSubscriber::class);
    $parameters->set('sulu.persistence.event_subscriber.orm.metadata.class', MetadataSubscriber::class);

    $services->set('sulu.persistence.event_subscriber.orm.timestampable', '%sulu.persistence.event_subscriber.orm.timestampable.class%')
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata'])
        ->tag('doctrine.event_listener', ['event' => 'preUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'prePersist']);

    // Priority 50 as the MetadataLoader need to be before the ResolveTargetEntityListener of Doctrine
    $services->set('sulu.persistence.event_subscriber.orm.user_blame', '%sulu.persistence.event_subscriber.orm.user_blame.class%')
        ->args([new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 50])
        ->tag('doctrine.event_listener', ['event' => 'onFlush', 'priority' => 50]);

    // Priority 8000 as the MetadataLoader need to be before all other Doctrine listeners
    $services->set('sulu.persistence.event_subscriber.orm.metadata', '%sulu.persistence.event_subscriber.orm.metadata.class%')
        ->args(['%sulu.persistence.objects%'])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 8000]);
};
