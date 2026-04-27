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

use Sulu\Bundle\CoreBundle\Build\DatabaseBuilder;
use Sulu\Bundle\CoreBundle\Build\FixturesBuilder;
use Sulu\Bundle\CoreBundle\Build\PhpcrBuilder;
use Sulu\Bundle\CoreBundle\Build\PhpcrMigrationsBuilder;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_core.build.builder.database.class', DatabaseBuilder::class);
    $parameters->set('sulu_core.build.builder.phpcr.class', PhpcrBuilder::class);
    $parameters->set('sulu_core.build.builder.phpcr_migrations.class', PhpcrMigrationsBuilder::class);
    $parameters->set('sulu_core.build.builder.fixtures.class', FixturesBuilder::class);

    $services->set('sulu_core.build.builder.database', '%sulu_core.build.builder.database.class%')
        ->tag('massive_build.builder');

    $services->set('sulu_core.build.builder.phpcr', '%sulu_core.build.builder.phpcr.class%')
        ->tag('massive_build.builder');

    $services->set('sulu_core.build.builder.phpcr_migrations', '%sulu_core.build.builder.phpcr_migrations.class%')
        ->args([
            new Reference('phpcr_migrations.migrator_factory'),
            new Reference('phpcr_migrations.version_storage'),
        ])
        ->tag('massive_build.builder');

    $services->set('sulu_core.build.builder.fixtures', '%sulu_core.build.builder.fixtures.class%')
        ->tag('massive_build.builder');
};
