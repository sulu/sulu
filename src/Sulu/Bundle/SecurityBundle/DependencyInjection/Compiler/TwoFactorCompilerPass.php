<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwoFactorCompilerPass implements CompilerPassInterface
{
    /**
     * Trusting a device skips the second factor for a while, it never is one on its own,
     * so it must not count as a method a user can set up.
     */
    private const TRUSTED_DEVICES_METHOD = 'trusted_devices';

    public function process(ContainerBuilder $container): void
    {
        $methods = [];

        if ($container->has('scheb_two_factor.security.email.code_generator')) {
            $methods[] = 'email';
        }

        if ($container->has('scheb_two_factor.security.totp_authenticator')) {
            $methods[] = 'totp';
        }

        if ($container->has('scheb_two_factor.security.google_authenticator')) {
            $methods[] = 'google';
        }

        if ($container->hasParameter('scheb_two_factor.trusted_device.enabled')
            && $container->getParameter('scheb_two_factor.trusted_device.enabled')
        ) {
            $methods[] = self::TRUSTED_DEVICES_METHOD;
        }

        $setupMethods = \array_values(\array_diff($methods, [self::TRUSTED_DEVICES_METHOD]));

        $container->setParameter('sulu_security.two_factor_methods', $methods);
        $container->setParameter('sulu_security.two_factor_setup_methods', $setupMethods);

        $container->setParameter(
            'sulu_security.two_factor_backup_codes_enabled',
            $container->has('scheb_two_factor.backup_code_manager'),
        );

        // a second factor can not be forced without a method the user is able to activate
        if (0 === \count($setupMethods)) {
            $container->setParameter('sulu_security.two_factor_force_pattern', null);
            $container->setParameter('sulu_security.two_factor_force_setup', false);
            $container->removeDefinition('sulu_security.force_two_factor_listener');

            return;
        }

        // without the email method the legacy default of activating "email" for forced users would
        // leave them without any working provider, so they have to set up a method themselves
        if ($container->getParameter('sulu_security.two_factor_force_pattern')
            && !\in_array('email', $setupMethods, true)
        ) {
            $container->setParameter('sulu_security.two_factor_force_setup', true);
            $container->removeDefinition('sulu_security.force_two_factor_listener');
        }
    }
}
