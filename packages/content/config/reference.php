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

use Sulu\Content\Infrastructure\Sulu\Reference\ReferenceDoctrineEventListener;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.reference_doctrine_event_listener', ReferenceDoctrineEventListener::class)
        ->args([
            new Reference('sulu_message_bus'),
            new Reference('sulu_reference.reference_repository'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'prePersist', 'lazy' => true, 'method' => 'prePersist'])
        ->tag('doctrine.event_listener', ['event' => 'preUpdate', 'lazy' => true, 'method' => 'preUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove', 'lazy' => true, 'method' => 'preRemove'])
        ->tag('doctrine.event_listener', ['event' => 'postFlush', 'lazy' => true, 'method' => 'postFlush'])
        ->tag('doctrine.event_listener', ['event' => 'onClear', 'lazy' => true, 'method' => 'onClear']);
};
