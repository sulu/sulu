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

use Sulu\Bundle\ReferenceBundle\Application\MessageHandler\RefreshReferenceMessageHandler;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Doctrine\Repository\ReferenceRepository;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactory;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactoryInterface;
use Sulu\Bundle\ReferenceBundle\UserInterface\Command\RefreshCommand;
use Sulu\Bundle\ReferenceBundle\UserInterface\Controller\Admin\ReferenceController;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_reference.reference_repository', ReferenceRepository::class)
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->alias(ReferenceRepositoryInterface::class, 'sulu_reference.reference_repository');

    $services->set('sulu_reference.reference_admin', ReferenceAdmin::class)
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_reference.reference_list_view_builder_factory', ReferenceViewBuilderFactory::class)
        ->args([
            new Reference('sulu_admin.view_builder_factory'),
            new Reference('sulu_security.security_checker'),
        ]);

    $services->alias(ReferenceViewBuilderFactoryInterface::class, 'sulu_reference.reference_list_view_builder_factory');

    $services->set('sulu_reference.reference_controller', ReferenceController::class)
        ->public()
        ->args([
            new Reference('sulu_reference.reference_repository'),
            new Reference('sulu_security.security_checker'),
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_reference.refresh_reference_message_handler', RefreshReferenceMessageHandler::class)
        ->args([
            new Reference('sulu_reference.reference_repository'),
            tagged_iterator('sulu_reference.refresher', indexAttribute: 'resourceKey', defaultIndexMethod: 'getResourceKey'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sulu_reference.refresh_command', RefreshCommand::class)
        ->args([
            tagged_iterator('sulu_reference.refresher', defaultIndexMethod: 'getResourceKey'),
            new Reference('sulu_reference.reference_repository'),
            '%sulu.context%',
        ])
        ->tag('console.command');
};
