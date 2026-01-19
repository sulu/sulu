<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\MediaBundle\EventListener\MediaAudienceTargetingSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.media_audience_targeting_subscriber', MediaAudienceTargetingSubscriber::class)
        // Priority 10 as the MetadataLoader need to be before the ResolveTargetEntityListener of doctrine
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 10]);
};
