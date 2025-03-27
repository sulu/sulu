<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @deprecated
 * This compiler pass removes legacy SuluPageBundle services when SuluNextPageBundle is enabled.
 * It is only used to ensure backwards compatibility and can be removed as soon as SuluPageBundle is removed.
 */
class SuluNextPageCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (\array_key_exists('SuluNextPageBundle', $container->getParameter('kernel.bundles'))) { // @phpstan-ignore function.impossibleType
            // Remove the old NavigationTwigExtension service when SuluNextPageBundle is enabled
            if ($container->hasDefinition('sulu_page.extension.navigation')) {
                $container->removeDefinition('sulu_page.extension.navigation');
            }
        }
    }
}
