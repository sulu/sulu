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

use Sulu\Bundle\CustomUrlBundle\EventListener\CustomUrlSerializeEventSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_custom_urls.serializer.event_subscriber', CustomUrlSerializeEventSubscriber::class)
        ->args([
            new Reference('sulu_custom_urls.domain_generator'),
            new Reference('sulu_security.user_manager'),
            new Reference('sulu_document_manager.document_inspector'),
        ])
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);
};
