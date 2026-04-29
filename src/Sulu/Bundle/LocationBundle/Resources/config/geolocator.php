<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\LocationBundle\Geolocator\Service\GoogleGeolocator;
use Sulu\Bundle\LocationBundle\Geolocator\Service\MapquestGeolocator;
use Sulu\Bundle\LocationBundle\Geolocator\Service\NominatimGeolocator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $parameters = $container->parameters();
    $parameters->set('sulu_location.geolocator.nominatim.class', NominatimGeolocator::class);
    $parameters->set('sulu_location.geolocator.google.class', GoogleGeolocator::class);
    $parameters->set('sulu_location.geolocator.mapquest.class', MapquestGeolocator::class);

    $services = $container->services();

    // Nominatim
    $services->set('sulu_location.geolocator.service.nominatim', '%sulu_location.geolocator.nominatim.class%')
        ->args([
            new Reference('http_client'),
            '%sulu_location.geolocator.service.nominatim.endpoint%',
            '%sulu_location.geolocator.service.nominatim.api_key%',
        ]);

    // Google
    $services->set('sulu_location.geolocator.service.google', '%sulu_location.geolocator.google.class%')
        ->args([
            new Reference('http_client'),
            '%sulu_location.geolocator.service.google.api_key%',
        ]);

    // Mapquest
    $services->set('sulu_location.geolocator.service.mapquest', '%sulu_location.geolocator.mapquest.class%')
        ->args([
            new Reference('http_client'),
            '%sulu_location.geolocator.service.mapquest.endpoint%',
            '%sulu_location.geolocator.service.mapquest.api_key%',
        ]);
};
