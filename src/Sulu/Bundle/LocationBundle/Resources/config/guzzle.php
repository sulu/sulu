<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function(ContainerConfigurator $container) {
    $parameters = $container->parameters();
    $parameters->set('sulu_location.guzzle.client.class', \GuzzleHttp\Client::class);

    $services = $container->services();
    $services->set('sulu_location.geolocator', '%sulu_location.guzzle.client.class%')
    ->deprecate('sulu/sulu', '2.1.9', '')
    ;
};
