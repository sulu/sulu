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

use Sulu\Bundle\SnippetBundle\Command\SnippetExportCommand;
use Sulu\Bundle\SnippetBundle\Command\SnippetImportCommand;
use Sulu\Bundle\SnippetBundle\Command\SnippetLocaleCopyCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_snippet.command.export', SnippetExportCommand::class)
        ->args([new Reference('sulu_snippet.export.snippet')])
        ->tag('console.command');

    $services->set('sulu_snippet.command.import', SnippetImportCommand::class)
        ->args([
            new Reference('sulu_snippet.import.snippet'),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('console.command');

    $services->set('sulu_snippet.command.locale_copy', SnippetLocaleCopyCommand::class)
        ->args([
            new Reference('sulu_snippet.repository'),
            new Reference('sulu.content.mapper'),
            new Reference('doctrine_phpcr.session'),
            new Reference('sulu_document_manager.document_manager'),
            '%sulu.content.language.namespace%',
        ])
        ->tag('console.command');
};
