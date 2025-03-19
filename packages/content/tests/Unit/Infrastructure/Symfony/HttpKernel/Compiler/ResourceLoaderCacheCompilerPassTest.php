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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ResourceLoaderCacheCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ResourceLoaderCacheCompilerPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $cachedResourceLoaderDefinition = $this->prophesize(Definition::class);

        $container->findTaggedServiceIds('sulu_content.resource_loader')
            ->shouldBeCalled()
            ->willReturn([
                'resource_loader.test' => [
                    ['type' => 'test'],
                ],
                'resource_loader.another' => [
                    ['type' => 'another'],
                ],
            ]);

        $container->register('resource_loader.test.cached', CachedResourceLoader::class)
            ->shouldBeCalled()
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $container->register('resource_loader.another.cached', CachedResourceLoader::class)
            ->shouldBeCalled()
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->setDecoratedService('resource_loader.test')
            ->shouldBeCalled()
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->setDecoratedService('resource_loader.another')
            ->shouldBeCalled()
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->setArguments([
            new Reference('resource_loader.test.cached.inner'),
        ])->shouldBeCalledTimes(1)
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->setArguments([
            new Reference('resource_loader.another.cached.inner'),
        ])->shouldBeCalledTimes(1)
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->setPublic(false)
            ->shouldBeCalledTimes(2)
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $container->getDefinition('resource_loader.test.cached')
            ->shouldBeCalled()
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $container->getDefinition('resource_loader.another.cached')
            ->shouldBeCalled()
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->addTag('sulu_content.resource_loader', ['type' => 'test'])
            ->shouldBeCalledTimes(1)
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $cachedResourceLoaderDefinition->addTag('sulu_content.resource_loader', ['type' => 'another'])
            ->shouldBeCalledTimes(1)
            ->willReturn($cachedResourceLoaderDefinition->reveal());

        $compilerPass = new ResourceLoaderCacheCompilerPass();
        $compilerPass->process($container->reveal());
    }

    public function testProcessWithNoResourceLoaders(): void
    {
        $container = $this->prophesize(ContainerBuilder::class);

        $container->findTaggedServiceIds('sulu_content.resource_loader')
            ->shouldBeCalled()
            ->willReturn([]);

        $container->register(Argument::any())->shouldNotBeCalled();
        $container->getDefinition(Argument::any())->shouldNotBeCalled();

        $compilerPass = new ResourceLoaderCacheCompilerPass();
        $compilerPass->process($container->reveal());
    }
}
