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

use Sulu\Component\Content\Extension\ExtensionManager;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_page.extension.manager.class', ExtensionManager::class);

    $services->set('sulu_page.extension.manager', '%sulu_page.extension.manager.class%')
        ->public();
};
