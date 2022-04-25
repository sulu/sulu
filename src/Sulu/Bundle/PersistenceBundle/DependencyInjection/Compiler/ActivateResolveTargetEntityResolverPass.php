<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler;

use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ActivateResolveTargetEntityResolverPass implements CompilerPassInterface
{
    /**
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
            throw new RuntimeException('Cannot find Doctrine Target Entity Resolver Listener.');
        }

        $resolveTargetEntityListener = $container->findDefinition('doctrine.orm.listeners.resolve_target_entity');

        // we need to make sure that the service is added as event subscriber when doctrine bundle is not doing it
        // when the bundle dont get any config
        if (!$resolveTargetEntityListener->hasTag('doctrine.event_subscriber')) {
            $resolveTargetEntityListener->addTag('doctrine.event_subscriber');
        }
    }
}
