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

use Sulu\Bundle\CoreBundle\Controller\LocalizationController;
use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Localization\Provider\LocalizationProvider;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu.core.localization_manager.class', LocalizationManager::class);
    $parameters->set('sulu.core.localization_manager.core_provider.class', LocalizationProvider::class);

    $services->set('sulu.core.localization_manager', '%sulu.core.localization_manager.class%')
        ->public();

    $services->alias(LocalizationManagerInterface::class, 'sulu.core.localization_manager');

    $services->set('sulu_core.localization_controller', LocalizationController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu.core.localization_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
