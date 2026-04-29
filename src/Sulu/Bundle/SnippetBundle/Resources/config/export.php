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

use Sulu\Component\Snippet\Export\SnippetExport;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_snippet.export.snippet.formats', ['1.2.xliff' => '@SuluPage/Export/Snippet/1.2.xliff.twig']);

    $services->set('sulu_snippet.export.snippet', SnippetExport::class)
        ->public()
        ->args([
            new Reference('twig'),
            new Reference('sulu_snippet.repository'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_page.export.manager'),
            '%sulu_snippet.export.snippet.formats%',
        ]);
};
