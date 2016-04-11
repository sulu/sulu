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
 * Add all services with given tag to the bundle content navigation.
 */
class ContentNavigationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $contentNavigationCollector = $container->getDefinition('sulu_admin.content_navigation_registry');

        $taggedServices = $container->findTaggedServiceIds('sulu_admin.content_navigation');

        foreach ($taggedServices as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'No "alias" specified for content navigation provider with service ID: "%s"',
                        $id
                    )
                );
            }

            $contentNavigationCollector->addMethodCall(
                'addContentNavigationProvider',
                [$attributes[0]['alias'], $id]
            );
        }
    }
}
