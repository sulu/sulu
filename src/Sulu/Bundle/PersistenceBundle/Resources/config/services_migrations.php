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

use Sulu\Component\Persistence\Migrations\MigrationFactoryDecorator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(MigrationFactoryDecorator::class)
        ->decorate('doctrine.migrations.migrations_factory')
        ->args([
            new Reference('.inner'),
            '%sulu.persistence.legacy_length%',
        ]);
};
