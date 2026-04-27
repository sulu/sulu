<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\LocationBundle\Content\Types\LocationContentType;
use Sulu\Bundle\LocationBundle\Controller\GeolocatorController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $parameters = $container->parameters();
    $parameters->set('sulu_location.content.type.location.class', LocationContentType::class);

    $services = $container->services();

    // Content Types
    $services->set('sulu_location.content.type.location', '%sulu_location.content.type.location.class%')
        ->tag('sulu.content.type', ['alias' => 'location'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => 'false'])
    ;

    // Controller
    $services->set('sulu_location.controller.geolocator', GeolocatorController::class)
        ->public()
        ->args([new Reference('sulu_location.geolocator')]);
};
