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

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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
            $this->removeLegacyNavigationTwigExtension($container);

            // Remove old page admin
            $this->removeLegacyPageAdmin($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function removeLegacyPageAdmin(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('sulu_page.admin')) {
            $adminPool = $container->getDefinition('sulu_admin.admin_pool');

            $methodCalls = $adminPool->getMethodCalls();
            foreach ($methodCalls as $key => $methodCall) {
                if ($methodCall[0] === 'addAdmin') {
                    /** @var Definition $definition */
                    $definition = $methodCall[1][0];
                    if ($definition->getClass() === '%sulu_page.admin.class%') {
                        unset($methodCalls[$key]);
                    }
                }
            }
            $adminPool->setMethodCalls($methodCalls);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function removeLegacyNavigationTwigExtension(ContainerBuilder $container): void
    {
        $twigDefinition = $container->getDefinition('twig');

        $methodCalls = $twigDefinition->getMethodCalls();
        foreach ($methodCalls as $key => $methodCall) {
            if ($methodCall[0] === 'addExtension' && ((string)$methodCall[1][0]) === 'sulu_website.twig.navigation.memoized') {
                unset($methodCalls[$key]);
            }
        }
        $twigDefinition->setMethodCalls($methodCalls);
    }

}
