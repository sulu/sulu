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

use Sulu\Bundle\PageBundle\Command\CleanupHistoryCommand;
use Sulu\Bundle\PageBundle\Command\ContentLocaleCopyCommand;
use Sulu\Bundle\PageBundle\Command\ContentTypesDumpCommand;
use Sulu\Bundle\PageBundle\Command\MaintainResourceLocatorCommand;
use Sulu\Bundle\PageBundle\Command\ValidatePagesCommand;
use Sulu\Bundle\PageBundle\Command\ValidateWebspacesCommand;
use Sulu\Bundle\PageBundle\Command\WebspaceCopyCommand;
use Sulu\Bundle\PageBundle\Command\WebspaceExportCommand;
use Sulu\Bundle\PageBundle\Command\WebspaceImportCommand;
use Sulu\Bundle\PageBundle\Command\WorkspaceImportCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.command.maintain_resource_locator', MaintainResourceLocatorCommand::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.cleanup_history', CleanupHistoryCommand::class)
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.copy_locale', ContentLocaleCopyCommand::class)
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('doctrine_phpcr.session'),
            '%sulu.content.language.namespace%',
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.dump_content_types', ContentTypesDumpCommand::class)
        ->tag('console.command');

    $services->set('sulu_page.command.validate_pages', ValidatePagesCommand::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.content.webspace_structure_provider'),
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.validate_webspaces', ValidateWebspacesCommand::class)
        ->args([
            new Reference('twig'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_page.controller_name_converter', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.content.webspace_structure_provider'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('event_dispatcher'),
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.webspace_copy', WebspaceCopyCommand::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_markup.parser.html_extractor'),
            new Reference('sulu_page.structure.factory'),
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.webspace_export', WebspaceExportCommand::class)
        ->args([new Reference('sulu_page.export.webspace')])
        ->tag('console.command');

    $services->set('sulu_page.command.webspace_import', WebspaceImportCommand::class)
        ->args([
            new Reference('sulu_page.import.webspace'),
            new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ])
        ->tag('console.command');

    $services->set('sulu_page.command.workspace_import', WorkspaceImportCommand::class)
        ->tag('console.command');
};
