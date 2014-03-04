<?php
/*
 * This file is part of the Sulu CMS.
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
 * Add all admin-services with the tag "sulu.admin" to the AdminPool-Service
 *
 * @package Sulu\Bundle\AdminBundle\DependencyInjection\Compiler
 */
class AddJsConfigPass implements CompilerPassInterface
{

    const CONFIG_TAG = 'sulu.jsconfig';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sulu_admin.jsconfig_pool');

        $taggedServices = $container->findTaggedServiceIds(self::CONFIG_TAG);

        foreach ($taggedServices as $id => $attributes) {
            $config = $container->getDefinition($id);
            $pool->addMethodCall('addConfigParams', array($config));
        }
    }
}
