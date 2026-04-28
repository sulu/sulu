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

use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.compat.structure.legacy_property_factory', LegacyPropertyFactory::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.namespace_registry'),
            new Reference('sulu_page.structure.factory'),
        ]);
};
