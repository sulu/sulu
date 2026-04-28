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

use Sulu\Bundle\DocumentManagerBundle\Command\FixturesLoadCommand;
use Sulu\Bundle\DocumentManagerBundle\Command\InitializeCommand;
use Sulu\Bundle\DocumentManagerBundle\Command\PHPCRCleanupCommand;
use Sulu\Bundle\DocumentManagerBundle\Command\PHPCRCleanupSingleNodeCommand;
use Sulu\Bundle\DocumentManagerBundle\Command\SubscriberDebugCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.command.fixtures_load', FixturesLoadCommand::class)
        ->args([
            new Reference('sulu_document_manager.data_fixtures.executor'),
            tagged_iterator('sulu.document_manager_fixture'),
        ])
        ->tag('console.command');

    $services->set('sulu_document_manager.command.initialize', InitializeCommand::class)
        ->args([new Reference('sulu_document_manager.initializer')])
        ->tag('console.command');

    $services->set('sulu_document_manager.command.subscriber_debug', SubscriberDebugCommand::class)
        ->args([new Reference('sulu_document_manager.event_dispatcher')])
        ->tag('console.command');

    $services->set('sulu_document_manager.command.phpcr_cleanup', PHPCRCleanupCommand::class)
        ->args([
            new Reference('doctrine_phpcr.live_session'),
            new Reference('doctrine_phpcr.default_session'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('services_resetter'),
            '%kernel.project_dir%',
            new Reference('doctrine_phpcr.nodes_cache_pool', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('doctrine_phpcr.meta_cache_pool', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('console.command');

    $services->set('sulu_document_manager.command.phpcr_cleanup_single_node', PHPCRCleanupSingleNodeCommand::class)
        ->args([
            new Reference('doctrine_phpcr.live_session'),
            new Reference('doctrine_phpcr.default_session'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.namespace_registry'),
            new Reference('sulu_document_manager.event_dispatcher'),
            new Reference('sulu_document_manager.document_manager'),
            '%sulu_document_manager.mapping%',
            new Reference('sulu_core.webspace.webspace_manager'),
        ])
        ->tag('console.command');
};
