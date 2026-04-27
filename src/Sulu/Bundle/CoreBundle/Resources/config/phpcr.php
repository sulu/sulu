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

use Sulu\Component\PHPCR\SessionManager\SessionManager;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu.phpcr.session.class', SessionManager::class);

    $services->set('sulu.phpcr.session', '%sulu.phpcr.session.class%')
        ->public()
        ->args([
            new Reference('sulu_document_manager.default_session'),
            [
                'base' => '%sulu.content.node_names.base%',
                'content' => '%sulu.content.node_names.content%',
                'route' => '%sulu.content.node_names.route%',
                'snippet' => '%sulu.content.node_names.snippet%',
            ],
        ]);
};
