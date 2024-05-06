<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class RegisterPreviewWebspaceClassPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_core')) {
            $isPreview = $container->hasParameter('sulu.preview')
                ? true === $container->getParameter('sulu.preview')
                : false;

            if ($isPreview) {
                $container->setParameter(
                    'sulu_core.webspace.cache_class',
                    'preview' . $container->getParameter('sulu_core.webspace.cache_class')
                );
            }
        }
    }
}
