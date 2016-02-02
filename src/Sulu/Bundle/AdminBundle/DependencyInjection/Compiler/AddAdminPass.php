<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add all admin-services with the tag "sulu.admin" to the AdminPool-Service.
 */
class AddAdminPass implements CompilerPassInterface
{
    const ADMIN_TAG = 'sulu.admin';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sulu_admin.admin_pool');

        $taggedServices = $container->findTaggedServiceIds(self::ADMIN_TAG);

        foreach ($taggedServices as $id => $attributes) {
            $admin = $container->getDefinition($id);
            $pool->addMethodCall('addAdmin', [$admin]);
        }
    }
}
