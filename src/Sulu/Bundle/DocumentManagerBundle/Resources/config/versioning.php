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

use Sulu\Component\DocumentManager\Subscriber\Behavior\VersionSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.suscriber.behavior.version', VersionSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('sulu_document_manager.event_subscriber');
};
