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

use Sulu\Bundle\MediaBundle\EventListener\MediaAudienceTargetingSubscriber;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.media_audience_targeting_subscriber', MediaAudienceTargetingSubscriber::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 10]);
};
