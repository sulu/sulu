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

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.command.create_role', CreateRoleCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sulu.repository.role'),
            service('sulu_admin.admin_pool'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.init', \Sulu\Bundle\SecurityBundle\Command\InitCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sulu.repository.role'),
            service('sulu_admin.admin_pool'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.create_user', \Sulu\Bundle\SecurityBundle\Command\CreateUserCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sulu.repository.user'),
            service('sulu.repository.role'),
            service('sulu.repository.contact'),
            service('sulu.core.localization_manager'),
            service('sulu_security.salt_generator'),
            service('sulu_security.encoder_factory'),
            '%sulu_core.locales%',
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.reset_two_factor', \Sulu\Bundle\SecurityBundle\Command\ResetTwoFactorCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sulu.repository.user'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.command.sync_phpcr_permissions', \Sulu\Bundle\SecurityBundle\Command\SyncPhpcrPermissionsCommand::class)
        ->args([
            service('doctrine.orm.default_entity_manager'),
            service('sulu_document_manager.document_manager'),
            service('sulu_security.doctrine_access_control_provider'),
        ])
        ->tag('console.command')
        ->tag('sulu.context', ['context' => 'admin']);
};
