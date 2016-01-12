<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add path segments for custom-urls.
 */
class PathSegmentCompilerPass implements CompilerPassInterface
{
    const PATH_SEGMENT_REGISTRY_ID = 'sulu_document_manager.path_segment_registry';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::PATH_SEGMENT_REGISTRY_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::PATH_SEGMENT_REGISTRY_ID);
        $definition->addMethodCall('add', ['custom-urls', 'custom-urls']);
        $definition->addMethodCall('add', ['custom-urls-items', 'items']);
        $definition->addMethodCall('add', ['custom-urls-routes', 'routes']);
    }
}
