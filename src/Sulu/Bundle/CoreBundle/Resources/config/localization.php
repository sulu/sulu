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

use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu.core.localization_manager', LocalizationManager::class)
        ->public()
        ->args([tagged_iterator('sulu.localization_provider')]);

    $services->alias(LocalizationManagerInterface::class, 'sulu.core.localization_manager');
};
