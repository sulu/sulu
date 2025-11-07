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

/** @deprecated use tagged_iterator instead */
class AccessControlProviderPass implements CompilerPassInterface
{
    /** @deprecated use Sulu\Component\Security\Authorization\AccessControl\AccessControlProviderInterface::SERVICE_TAG instead */
    public const ACCESS_CONTROL_TAG = 'sulu.access_control';

    public function process(ContainerBuilder $container)
    {
        $accessControlManager = $container->getDefinition('sulu_security.access_control_manager');

        $taggedServices = $container->findTaggedServiceIds(static::ACCESS_CONTROL_TAG);

        foreach ($taggedServices as $id => $attributes) {
            $accessControlProvider = $container->getDefinition($id);
            $accessControlManager->addMethodCall('addAccessControlProvider', [$accessControlProvider]);
        }
    }
}
