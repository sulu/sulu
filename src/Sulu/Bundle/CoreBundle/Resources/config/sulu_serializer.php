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

use Sulu\Component\Serializer\SuluSerializer;
use Sulu\Component\Serializer\SuluSerializerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->set('sulu_core.sulu_serializer', SuluSerializer::class)
        ->public()
        ->autowire()
        ->args([new Reference('serializer')]);

    $services->alias(SuluSerializerInterface::class, 'sulu_core.sulu_serializer');
};
