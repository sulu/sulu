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

use Sulu\Bundle\DocumentManagerBundle\Routing\Loader\VersionRouteLoader;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.routing.version_loader', VersionRouteLoader::class)
        ->args(['%sulu_document_manager.versioning.enabled%'])
        ->tag('routing.loader');
};
