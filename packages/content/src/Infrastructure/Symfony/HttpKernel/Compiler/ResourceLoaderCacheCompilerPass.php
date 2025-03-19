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
            $container->register($id . '.cached', CachedResourceLoader::class)
                ->setDecoratedService($id)  // This service decorates the original loader
                ->setArguments([
                    new Reference($id . '.cached.inner'),
                ])
                ->setPublic(false);

            /** @var array<string, string> $tag */
            foreach ($tags as $tag) {
                $container->getDefinition($id . '.cached')->addTag('sulu_content.resource_loader', $tag);
            }
        }
    }
}
