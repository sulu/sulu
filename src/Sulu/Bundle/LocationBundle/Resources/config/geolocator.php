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

use Sulu\Bundle\LocationBundle\Geolocator\Service\GoogleGeolocator;
use Sulu\Bundle\LocationBundle\Geolocator\Service\MapquestGeolocator;
use Sulu\Bundle\LocationBundle\Geolocator\Service\NominatimGeolocator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_location.geolocator.service.nominatim', NominatimGeolocator::class)
        ->args([
            new Reference('http_client'),
            '%sulu_location.geolocator.service.nominatim.endpoint%',
            '%sulu_location.geolocator.service.nominatim.api_key%',
        ]);

    $services->set('sulu_location.geolocator.service.google', GoogleGeolocator::class)
        ->args([
            new Reference('http_client'),
            '%sulu_location.geolocator.service.google.api_key%',
        ]);

    $services->set('sulu_location.geolocator.service.mapquest', MapquestGeolocator::class)
        ->args([
            new Reference('http_client'),
            '%sulu_location.geolocator.service.mapquest.endpoint%',
            '%sulu_location.geolocator.service.mapquest.api_key%',
        ]);
};
