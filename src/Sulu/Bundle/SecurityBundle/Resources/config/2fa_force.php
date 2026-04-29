<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\SecurityBundle\EventListener\ForceTwoFactorSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.force_two_factor_listener', ForceTwoFactorSubscriber::class)
        ->args(['%sulu_security.two_factor_force_pattern%'])
        ->tag('doctrine.event_listener', ['event' => 'preUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'prePersist']);
};
