<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler;

use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResourceLoaderCacheCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resourceLoaders = $container->findTaggedServiceIds('sulu_content.resource_loader');

        foreach ($resourceLoaders as $id => $tags) {
            $decoratedService = $container->getDefinition($id);

            // Create the cached decorator service
            $decoratorId = $id . '.cached';
            $container->register($decoratorId, CachedResourceLoader::class)
                ->setDecoratedService($id)
                ->setArguments([
                    new Reference($decoratorId . '.inner'),
                ])
                ->addTag('kernel.reset', ['method' => 'reset'])
                ->setPublic(false);

            $decoratorService = $container->getDefinition($decoratorId);

            // Copy all tags from the original service to the decorator to prevent tag loss during service decoration
            // Refer to: https://symfony.com/doc/current/service_container/service_decoration.html
            $tags = $decoratedService->getTags();
            foreach ($tags as $tagName => $tagAttributes) {
                if (\is_array($tagAttributes)) {
                    foreach ($tagAttributes as $attributes) {
                        if (\is_array($attributes)) {
                            $decoratorService->addTag($tagName, $attributes);
                        }
                    }
                }
            }
        }
    }
}
