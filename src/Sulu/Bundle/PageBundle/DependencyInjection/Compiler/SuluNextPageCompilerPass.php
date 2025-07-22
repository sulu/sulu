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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
        if (\array_key_exists('SuluNextPageBundle', $container->getParameter('kernel.bundles'))) {
            // Remove the old NavigationTwigExtension service when SuluNextPageBundle is enabled
            $this->removeLegacyNavigationTwigExtension($container);

            // Remove old page admin
            $this->removeLegacyPageAdmin($container);
        }
    }

    public function removeLegacyPageAdmin(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('sulu_page.admin')) {
            $adminPool = $container->getDefinition('sulu_admin.admin_pool');

            $methodCalls = $adminPool->getMethodCalls();
            /**
             * @var string $key
             * @var array<int, string|array<int, Definition>> $methodCall
             */
            foreach ($methodCalls as $key => $methodCall) {
                if ('addAdmin' === $methodCall[0]) {
                    $definition = $methodCall[1][0];
                    if ($definition instanceof Definition) {
                        $class = $definition->getClass();
                        if (PageAdmin::class === $class) {
                            unset($methodCalls[$key]);
                            break;
                        }
                    }
                }
            }
            $adminPool->setMethodCalls($methodCalls);
        }
    }

    public function removeLegacyNavigationTwigExtension(ContainerBuilder $container): void
    {
        $twigDefinition = $container->getDefinition('twig');

        $methodCalls = $twigDefinition->getMethodCalls();
        /**
         * @var string $key
         * @var array<int, string|array<int, Reference>> $methodCall
         */
        foreach ($methodCalls as $key => $methodCall) {
            if ('addExtension' === $methodCall[0]) {
                $definition = $methodCall[1][0];
                if ($definition instanceof Reference) {
                    $id = $definition->__toString();
                    if ('sulu_website.twig.navigation.memoized' === $id) {
                        unset($methodCalls[$key]);
                        break;
                    }
                }
            }
        }
        $twigDefinition->setMethodCalls($methodCalls);
    }
}
