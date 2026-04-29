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

use Sulu\Bundle\PageBundle\Reference\Provider\PageReferenceProvider;
use Sulu\Bundle\PageBundle\Reference\Refresh\PageReferenceRefresher;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.reference_provider', PageReferenceProvider::class)
        ->args([
            new Reference('sulu.content.type_manager'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu_reference.reference_repository'),
        ])
        ->tag('sulu_document_manager.reference_provider');

    $services->set('sulu_page.page_reference_refresher', PageReferenceRefresher::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_page.reference_provider'),
            '%sulu.context%',
        ])
        ->tag('sulu_reference.refresher');
};
