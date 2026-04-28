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

use Sulu\Component\Serializer\ArraySerializer;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\Serializer\RepresentationSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.array_serializer', ArraySerializer::class)
        ->public()
        ->args([new Reference('jms_serializer')]);

    $services->alias(ArraySerializerInterface::class, 'sulu_core.array_serializer');

    $services->set('sulu_core.representation_handler', RepresentationSubscriber::class)
        ->tag('jms_serializer.event_subscriber');
};
