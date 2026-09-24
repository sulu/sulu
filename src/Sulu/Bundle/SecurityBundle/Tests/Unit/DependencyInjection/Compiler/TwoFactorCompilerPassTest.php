<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler\TwoFactorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwoFactorCompilerPassTest extends TestCase
{
    public function testForcesSetupWithoutEmailMethod(): void
    {
        $container = $this->createContainer();
        $container->register('scheb_two_factor.security.totp_authenticator');
        $container->setParameter('sulu_security.two_factor_force_pattern', '/(.+)/');
        $container->setParameter('sulu_security.two_factor_force_setup', false);

        (new TwoFactorCompilerPass())->process($container);

        $this->assertTrue($container->getParameter('sulu_security.two_factor_force_setup'));
        $this->assertFalse($container->hasDefinition('sulu_security.force_two_factor_listener'));
    }

    public function testDoesNotForceSetupWithEmailMethod(): void
    {
        $container = $this->createContainer();
        $container->register('scheb_two_factor.security.email.code_generator');
        $container->setParameter('sulu_security.two_factor_force_pattern', '/(.+)/');
        $container->setParameter('sulu_security.two_factor_force_setup', false);

        (new TwoFactorCompilerPass())->process($container);

        $this->assertFalse($container->getParameter('sulu_security.two_factor_force_setup'));
        $this->assertTrue($container->hasDefinition('sulu_security.force_two_factor_listener'));
    }

    public function testRemovesForcePatternWithoutAnySetupMethod(): void
    {
        $container = $this->createContainer();
        $container->setParameter('sulu_security.two_factor_force_pattern', '/(.+)/');
        $container->setParameter('sulu_security.two_factor_force_setup', false);

        (new TwoFactorCompilerPass())->process($container);

        // a second factor can not be forced without a method the user is able to activate
        $this->assertFalse($container->getParameter('sulu_security.two_factor_force_setup'));
        $this->assertFalse($container->hasDefinition('sulu_security.force_two_factor_listener'));
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('scheb_two_factor.trusted_device.enabled', false);
        $container->register('sulu_security.force_two_factor_listener');

        return $container;
    }
}
