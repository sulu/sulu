<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass, which adds the request analyzer as a service if required.
 */
class RequestAnalyzerCompilerPass implements CompilerPassInterface
{
    const TAG_NAME = 'sulu.request_attributes';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $references = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = array_key_exists('priority', $attributes) ? $attributes['priority'] : 0;
                $references[$priority][] = new Reference($id);
            }
        }

        krsort($references);
        $references = call_user_func_array('array_merge', $references);

        $container->getDefinition('sulu_core.webspace.request_analyzer')->replaceArgument(1, $references);
    }
}
