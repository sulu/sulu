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

use Sulu\Bundle\MediaBundle\Search\Subscriber\MediaSearchSubscriber;
use Sulu\Bundle\MediaBundle\Search\Subscriber\StructureMediaSearchSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_media.search.subscriber.structure_media.class', StructureMediaSearchSubscriber::class);
    $parameters->set('sulu_media.search.subscriber.media.class', MediaSearchSubscriber::class);

    $services->set('sulu_media.search.subscriber.structure_media', '%sulu_media.search.subscriber.structure_media.class%')
        ->args([
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_core.webspace.request_analyzer', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            '%sulu_media.search.default_image_format%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_media.search.subscriber.media', '%sulu_media.search.subscriber.media.class%')
        ->args([
            new Reference('sulu_media.media_manager'),
            new Reference('massive_search.factory'),
            '%sulu_media.format_manager.mime_types%',
            '%sulu_media.search.default_image_format%',
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('kernel.event_subscriber');
};
