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

use Sulu\Bundle\PageBundle\Controller\VersionController;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.version_controller', VersionController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.list_rest_helper'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_core.webspace.request_analyzer'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
