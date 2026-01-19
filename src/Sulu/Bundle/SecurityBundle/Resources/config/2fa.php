<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\SecurityBundle\Security\TwoFactorAuthenticationFailureHandler;
use Sulu\Bundle\SecurityBundle\Security\TwoFactorAuthenticationRequiredHandler;
use Sulu\Bundle\SecurityBundle\Security\TwoFactorAuthenticationSuccessHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.two_factor_authentication_required_handler', TwoFactorAuthenticationRequiredHandler::class);

    $services->set('sulu_security.two_factor_authentication_success_handler', TwoFactorAuthenticationSuccessHandler::class);

    $services->set('sulu_security.two_factor_authentication_failure_handler', TwoFactorAuthenticationFailureHandler::class);
};
