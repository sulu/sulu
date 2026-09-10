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

use Sulu\Bundle\MediaBundle\EventListener\ParallelImageGenerationLimiter;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.parallel_image_generation.limiter', ParallelImageGenerationLimiter::class)
        ->args([
            new Reference('semaphore.factory'),
            '%sulu_media.parallel_image_generation.limit%',
        ])
        ->tag('kernel.event_subscriber');
};
