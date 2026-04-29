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

use Sulu\Bundle\PageBundle\Twig\ExportTwigExtension;
use Sulu\Component\Content\Export\WebspaceExport;
use Sulu\Component\Content\Import\WebspaceImport;
use Sulu\Component\Export\Manager\ExportManager;
use Sulu\Component\Import\Format\Xliff12;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_page.export.webspace.formats', ['1.2.xliff' => '@SuluPage/Export/Webspace/1.2.xliff.twig']);

    $services->set('sulu_page.export.manager', ExportManager::class)
        ->args([new Reference('sulu.content.type_manager')]);

    $services->set('sulu_page.export.webspace', WebspaceExport::class)
        ->public()
        ->args([
            new Reference('twig'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu_page.export.manager'),
            '%sulu_page.export.webspace.formats%',
        ]);

    $services->set('sulu_page.import.webspace', WebspaceImport::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_page.compat.structure.legacy_property_factory'),
            new Reference('sulu.content.rlp.strategy.tree'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu_page.import.manager'),
            new Reference('sulu_page.import.webspace.xliff12'),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_page.import.webspace.xliff12', Xliff12::class)
        ->tag('sulu.content.import.service', ['format' => '1.2.xliff']);

    $services->set('sulu_page.export_twig_extension', ExportTwigExtension::class)
        ->args([new Reference('sulu_page.export.manager')])
        ->tag('twig.extension');
};
