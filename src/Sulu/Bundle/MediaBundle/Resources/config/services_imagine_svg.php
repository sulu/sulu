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

use Contao\ImagineSvg\Imagine;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.adapter.svg', Imagine::class)
        ->private();
};
