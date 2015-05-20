<?php
/*
 * This file is part of the Sulu CMS.
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

class CurrentUserDataCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('security.context')) {
            $container->setDefinition(
                'sulu_security.user_manager.current_user_data',
                new Definition('Sulu\Bundle\SecurityBundle\UserManager\CurrentUserData', array(
                        new Reference('security.context'),
                        new Reference('router'),
                        new Reference('doctrine'),
                    )
                )
            );

            $container->setDefinition(
                'sulu_security.user_manager',
                new Definition(
                    'Sulu\Bundle\SecurityBundle\UserManager\UserManager',
                    array(
                        new Reference('doctrine'),
                        new Reference('sulu_security.user_manager.current_user_data'),
                    )
                )
            );
        } else {
            $container->setDefinition(
                'sulu_security.user_manager',
                new Definition(
                    'Sulu\Bundle\SecurityBundle\UserManager\UserManager',
                    array(
                        new Reference('doctrine'),
                    )
                )
            );
        }
    }
}
