<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler\UserManagerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class UserManagerCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var UserManagerCompilerPass
     */
    private $userManagerCompilerPass;

    public function setUp()
    {
        $this->containerBuilder = $this->prophesize(ContainerBuilder::class);
        $this->userManagerCompilerPass = new UserManagerCompilerPass();
    }

    public function testProcessWithSecurity()
    {
        $this->containerBuilder->hasDefinition('security.token_storage')->willReturn(true);
        $this->containerBuilder->setDefinition(
            'sulu_security.user_manager',
            new Definition(
                'Sulu\Bundle\SecurityBundle\UserManager\UserManager',
                [
                    new Reference('doctrine.orm.entity_manager'),
                    new Reference('security.encoder_factory'),
                    new Reference('sulu.repository.role'),
                    new Reference('sulu_security.group_repository'),
                    new Reference('sulu_contact.contact_manager'),
                    new Reference('sulu_security.salt_generator'),
                    new Reference('sulu.repository.user'),
                ]
            )
        )->shouldBeCalled();
        $this->userManagerCompilerPass->process($this->containerBuilder->reveal());
    }

    public function testProcessWithoutSecurity()
    {
        $this->containerBuilder->hasDefinition('security.token_storage')->willReturn(false);
        $this->containerBuilder->setDefinition(
            'sulu_security.user_manager',
            new Definition(
                'Sulu\Bundle\SecurityBundle\UserManager\UserManager',
                [
                    new Reference('doctrine.orm.entity_manager'),
                    null,
                    new Reference('sulu.repository.role'),
                    new Reference('sulu_security.group_repository'),
                    new Reference('sulu_contact.contact_manager'),
                    new Reference('sulu_security.salt_generator'),
                    new Reference('sulu.repository.user'),
                ]
            )
        )->shouldBeCalled();
        $this->userManagerCompilerPass->process($this->containerBuilder->reveal());
    }
}
