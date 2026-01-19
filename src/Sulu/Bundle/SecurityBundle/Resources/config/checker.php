<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\SecurityBundle\EventListener\LastLoginListener;
use Sulu\Bundle\SecurityBundle\EventListener\SuluSecurityListener;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.security_checker', SecurityChecker::class)
        ->public()
        ->args([
            new Reference('security.token_storage'),
            new Reference('security.authorization_checker'),
        ]);

    $services->alias(SecurityCheckerInterface::class, 'sulu_security.security_checker');

    $services->set('sulu_security.event_listener.security', SuluSecurityListener::class)
        ->args([
            new Reference('sulu_security.security_checker'),
            new Reference('doctrine.orm.entity_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_security.last_login_listener', LastLoginListener::class)
        ->args([new Reference('doctrine.orm.entity_manager')])
        ->tag('kernel.event_subscriber');
};
