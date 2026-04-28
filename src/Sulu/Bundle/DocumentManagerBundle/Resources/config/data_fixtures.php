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

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.data_fixtures.executor', DocumentExecutor::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.initializer'),
        ]);
};
