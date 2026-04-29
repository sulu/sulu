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

use Sulu\Bundle\CategoryBundle\Command\RecoverCommand;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_category.command.recover', RecoverCommand::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.category'),
        ])
        ->tag('console.command');
};
