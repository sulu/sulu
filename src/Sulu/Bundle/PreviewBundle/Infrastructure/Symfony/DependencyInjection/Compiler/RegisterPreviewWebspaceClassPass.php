<?php

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
