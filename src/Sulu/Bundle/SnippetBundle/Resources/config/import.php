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

use Sulu\Component\Import\Format\Xliff12;
use Sulu\Component\Snippet\Import\SnippetImport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_snippet.import.snippet', SnippetImport::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_page.import.manager'),
            new Reference('sulu_page.compat.structure.legacy_property_factory'),
            new Reference('sulu_snippet.import.webspace.xliff12'),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_snippet.import.webspace.xliff12', Xliff12::class)
        ->tag('sulu.snippet.import.service', ['format' => '1.2.xliff']);
};
