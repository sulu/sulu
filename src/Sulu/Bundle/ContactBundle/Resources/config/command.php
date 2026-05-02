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

use Sulu\Bundle\ContactBundle\Command\AccountRecoverCommand;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_contact.command.recover', AccountRecoverCommand::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.account'),
        ])
        ->tag('console.command');
};
