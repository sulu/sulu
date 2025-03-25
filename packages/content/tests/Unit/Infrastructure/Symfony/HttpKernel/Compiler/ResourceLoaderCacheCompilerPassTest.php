<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Symfony\HttpKernel\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ResourceLoaderCacheCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ResourceLoaderCacheCompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ResourceLoaderCacheCompilerPass());
    }

    public function testCompilerPassDecorates(): void
    {
        $this->container->setDefinition('app.resource_loader.test', new Definition('stdClass'))
            ->addTag('sulu_content.resource_loader');

        $this->compile();

        $this->assertContainerBuilderHasService('app.resource_loader.test.cached', CachedResourceLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'app.resource_loader.test.cached',
            'setDecoratedService',
            ['app.resource_loader.test']
        );
    }

    public function testConfigurationPassesInnerServiceCorrectly(): void
    {
        $this->container->setDefinition('app.resource_loader.test', new Definition(ResourceLoaderInterface::class))
            ->addTag('sulu_content.resource_loader');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'app.resource_loader.test.cached',
            0,
            new Reference('app.resource_loader.test.cached.inner')
        );
    }

    public function testCompilerPassCopiesTags(): void
    {
        $this->container->setDefinition('app.resource_loader.test', new Definition(ResourceLoaderInterface::class))
            ->addTag('sulu_content.resource_loader', ['key' => 'value']);

        $this->compile();

        $definition = $this->container->getDefinition('app.resource_loader.test.cached');
        /** @var array<int, array<string, string>> $tags */
        $tags = $definition->getTag('sulu_content.resource_loader');
        $this->assertCount(1, $tags);
        $this->assertArrayHasKey('key', $tags[0]);
        $this->assertEquals('value', $tags[0]['key']);
    }
}
