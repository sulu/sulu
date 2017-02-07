<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class UserManagerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('security.token_storage')) {
            $container->setDefinition(
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
            );
        } else {
            $container->setDefinition(
                'sulu_security.user_manager',
                new Definition(
                    'Sulu\Bundle\SecurityBundle\UserManager\UserManager',
                    [
                        new Reference('doctrine.orm.entity_manager'),
                    ]
                )
            );
        }
    }
}
