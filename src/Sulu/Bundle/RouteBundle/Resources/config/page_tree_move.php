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

use Sulu\Bundle\RouteBundle\PageTree\PageTreeRepository;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_route.page_tree_route.mover', PageTreeRepository::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
        ]);
};
