<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass, which adds structure extension to structure manager
 * @package Sulu\Bundle\CoreBundle\DependencyInjection\Compiler
 */
class StructureExtensionCompilerPass implements CompilerPassInterface
{
    const STRUCTURE_EXTENSION_TAG = 'sulu.structure.extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_content.extension.manager')) {
            return;
        }

        $definition = $container->getDefinition('sulu_content.extension.manager');
        $taggedServices = $container->findTaggedServiceIds(self::STRUCTURE_EXTENSION_TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                if (isset($attributes['template'])) {
                    $params = array(new Reference($id), $attributes['template']);
                } else {
                    $params = array(new Reference($id));
                }

                $definition->addMethodCall('addExtension', $params);
            }
        }
    }
}
