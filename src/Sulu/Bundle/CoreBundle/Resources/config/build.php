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
use Sulu\Bundle\CoreBundle\Build\SearchBuilder;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.build.builder.database', DatabaseBuilder::class)
        ->tag('massive_build.builder');

    $services->set('sulu_core.build.builder.fixtures', FixturesBuilder::class)
        // additional dependencies, other bundles can append their builders via a compiler pass
        ->args([[]])
        ->tag('massive_build.builder');

    $services->set('sulu_core.build.builder.search', SearchBuilder::class)
        ->tag('massive_build.builder');
};
