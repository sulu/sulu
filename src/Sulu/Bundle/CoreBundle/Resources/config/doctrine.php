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

use Sulu\Component\Doctrine\ReferencesOption;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.doctrine.references', ReferencesOption::class)
        ->args([
            new Reference('doctrine'),
            [],
        ])
        ->tag('doctrine.event_listener', ['event' => 'postGenerateSchemaTable'])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);
};
