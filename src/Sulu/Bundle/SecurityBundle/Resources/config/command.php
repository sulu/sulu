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

use Sulu\Bundle\SecurityBundle\Command\CreateRoleCommand;
use Sulu\Bundle\SecurityBundle\Command\CreateUserCommand;
use Sulu\Bundle\SecurityBundle\Command\InitCommand;
use Sulu\Bundle\SecurityBundle\Command\LockUserCommand;
use Sulu\Bundle\SecurityBundle\Command\UnlockUserCommand;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.command.create_role', CreateRoleCommand::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.role'),
            new Reference('sulu_admin.admin_pool'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.init', InitCommand::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.role'),
            new Reference('sulu_admin.admin_pool'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.create_user', CreateUserCommand::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.user'),
            new Reference('sulu.repository.role'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu.core.localization_manager'),
            new Reference('sulu_security.salt_generator'),
            new Reference('security.password_hasher_factory'),
            '%sulu_core.locales%',
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.lock_user', LockUserCommand::class)
        ->args([
            new Reference('sulu.repository.user'),
            new Reference('sulu_security.user_manager'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.unlock_user', UnlockUserCommand::class)
        ->args([
            new Reference('sulu.repository.user'),
            new Reference('sulu_security.user_manager'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);
};
