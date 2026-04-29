<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\SecurityBundle\TwoFactor\AuthCodeMailer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.two_factor_mailer', AuthCodeMailer::class)
        ->args([
            new Reference('mailer.mailer'),
            new Reference('twig'),
            new Reference('translator'),
            '%sulu_security.two_factor_email_template%',
            '%scheb_two_factor.email.sender_email%',
            '%scheb_two_factor.email.sender_name%',
        ]);
};
