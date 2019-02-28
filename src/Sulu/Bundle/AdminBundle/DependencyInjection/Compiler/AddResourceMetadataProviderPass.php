<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add all resource metadata providers with the tag "sulu.resource_metadata_provider" to the `ResourceMetadataPool`.
 */
class AddResourceMetadataProviderPass implements CompilerPassInterface
{
    const TAG = 'sulu.resource_metadata_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sulu_admin.metadata.resource_metadata_pool');

        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $id => $attributes) {
            $pool->addMethodCall('addResourceMetadataProvider', [new Reference($id)]);
        }
    }
}
