<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\MarkupBundle\Listener\SwiftMailerListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_markup.swift_mailer_listener', SwiftMailerListener::class)
        ->args([
            tagged_iterator('sulu_markup.parser', indexAttribute: 'type'),
            new Reference(RequestStack::class),
        ])
        ->tag('swiftmailer.default.plugin')
    ;
};
