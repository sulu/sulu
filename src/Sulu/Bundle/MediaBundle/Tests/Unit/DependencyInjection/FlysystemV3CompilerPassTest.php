<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\DependencyInjection;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\FlysystemV3CompilerPass;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemV3Storage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FlysystemV3CompilerPassTest extends TestCase
{
    public function testRootPathOfLocalAdapter(): void
    {
        $container = $this->createContainer(LocalFilesystemAdapter::class, '/var/uploads');

        (new FlysystemV3CompilerPass())->process($container);

        $this->assertSame('/var/uploads', $container->getDefinition('sulu_media.storage')->getArgument(3));
    }

    public function testNoRootPathOfOtherAdapter(): void
    {
        $container = $this->createContainer(InMemoryFilesystemAdapter::class, null);

        (new FlysystemV3CompilerPass())->process($container);

        $this->assertNull($container->getDefinition('sulu_media.storage')->getArgument(3));
    }

    public function testRootPathOfAliasedLocalAdapter(): void
    {
        $container = $this->createContainer(LocalFilesystemAdapter::class, '/var/uploads');
        $container->setDefinition('custom.adapter', $container->getDefinition('flysystem.adapter.default.storage'));
        $container->removeDefinition('flysystem.adapter.default.storage');
        $container->setAlias('flysystem.adapter.default.storage', 'custom.adapter');

        (new FlysystemV3CompilerPass())->process($container);

        $this->assertSame('/var/uploads', $container->getDefinition('sulu_media.storage')->getArgument(3));
    }

    public function testOtherStorageIsIgnored(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('sulu_media.storage', new Definition(\stdClass::class));

        (new FlysystemV3CompilerPass())->process($container);

        $this->assertSame([], $container->getDefinition('sulu_media.storage')->getArguments());
    }

    public function testStorageAliasIsIgnored(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('sulu_media.storage.local', new Definition(\stdClass::class));
        $container->setAlias('sulu_media.storage', 'sulu_media.storage.local');

        (new FlysystemV3CompilerPass())->process($container);

        $this->assertSame([], $container->getDefinition('sulu_media.storage.local')->getArguments());
    }

    private function createContainer(string $adapterClass, ?string $adapterArgument): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('flysystem.adapter.default.storage', new Definition($adapterClass, [$adapterArgument]));
        $container->setDefinition(
            'sulu_media.storage',
            new Definition(FlysystemV3Storage::class, [
                new Reference('default.storage'),
                new Reference('flysystem.adapter.default.storage'),
                10,
                null,
            ])
        );

        return $container;
    }
}
