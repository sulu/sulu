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
        $this->container->setDefinition('app.resource_loader.test', new Definition(ResourceLoaderInterface::class))
            ->addTag('sulu_content.resource_loader', ['type' => 'test']);

        $this->compile();

        $this->assertContainerBuilderHasService('app.resource_loader.test.cached', CachedResourceLoader::class);
        $this->assertContainerBuilderServiceDecoration('app.resource_loader.test.cached', 'app.resource_loader.test');
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

    public function testKernelResetTagIsAdded(): void
    {
        $this->container->setDefinition('app.resource_loader.test', new Definition(ResourceLoaderInterface::class))
            ->addTag('sulu_content.resource_loader');

        $this->compile();

        $definition = $this->container->getDefinition('app.resource_loader.test.cached');
        $this->assertTrue($definition->hasTag('kernel.reset'));

        /** @var array<int, array<string, string>> $tags */
        $tags = $definition->getTag('kernel.reset');
        $this->assertCount(1, $tags);
        $this->assertArrayHasKey('method', $tags[0]);
        $this->assertEquals('reset', $tags[0]['method']);
    }

    public function testMultipleTagsAreCopied(): void
    {
        $this->container->setDefinition('app.resource_loader.test', new Definition(ResourceLoaderInterface::class))
            ->addTag('sulu_content.resource_loader')
            ->addTag('custom.tag.1', ['attr1' => 'value1'])
            ->addTag('custom.tag.2', ['attr2' => 'value2']);

        $this->compile();

        $definition = $this->container->getDefinition('app.resource_loader.test.cached');
        $this->assertTrue($definition->hasTag('custom.tag.1'));
        $this->assertTrue($definition->hasTag('custom.tag.2'));

        /** @var array<int, array<string, string>> $tags */
        $tags = $definition->getTag('custom.tag.1');
        $this->assertCount(1, $tags);
        $this->assertEquals('value1', $tags[0]['attr1']);

        /** @var array<int, array<string, string>> $tags */
        $tags = $definition->getTag('custom.tag.2');
        $this->assertCount(1, $tags);
        $this->assertEquals('value2', $tags[0]['attr2']);
    }
}
