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

use League\Flysystem\FilesystemOperator;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\DependencyInjection\SuluMediaExtension;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemV3Storage;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Process\ExecutableFinder;

class SuluMediaExtensionTest extends AbstractExtensionTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ExecutableFinder>
     */
    private $executableFinder;

    public function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinder::class);
        parent::setUp();
    }

    protected function getContainerExtensions(): array
    {
        return [
            new SuluMediaExtension($this->executableFinder->reveal()),
        ];
    }

    public function testLoad(): void
    {
        $this->executableFinder->find(Argument::any())->willReturn(true);
        $this->container->setParameter('kernel.bundles', []);

        $this->load(
            [
                'ffmpeg' => [
                    'ffmpeg_binary' => '/usr/local/bin/ffmpeg',
                    'ffprobe_binary' => '/usr/local/bin/ffprobe',
                ],
            ]
        );

        $this->assertContainerBuilderHasService('sulu_media.media_manager');
        $this->assertContainerBuilderHasParameter('sulu_media.format_manager.response_headers', [
            'Expires' => '+1 month',
            'Pragma' => 'public',
            'Cache-Control' => 'public, immutable, max-age=31536000',
        ]);
        $this->assertContainerBuilderHasParameter('sulu_media.search.default_image_format', 'sulu-100x100');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.path', '%kernel.project_dir%/var/uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.segments', 10);
        $this->assertContainerBuilderHasParameter('sulu_media.collection.type.default', [
            'id' => 1,
        ]);
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.save_image', true);
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.path', '%kernel.project_dir%/public/uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.media.blocked_file_types', []);
        $this->assertContainerBuilderHasParameter('sulu_media.ghost_script.path', 'gs');
        $this->assertContainerBuilderHasParameter(
            'sulu_media.format_manager.mime_types',
            ['image/*', 'video/*', 'application/pdf']
        );
        $this->assertContainerBuilderHasParameter('sulu_media.media.types', [
            [
                'type' => 'document',
                'mimeTypes' => ['*'],
            ],
            [
                'type' => 'image',
                'mimeTypes' => ['image/*'],
            ],
            [
                'type' => 'video',
                'mimeTypes' => ['video/*'],
            ],
            [
                'type' => 'audio',
                'mimeTypes' => ['audio/*'],
            ],
        ]);
    }

    public function testConfigureFileValidator(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load([
            'upload' => [
                'max_filesize' => 16,
                'blocked_file_types' => [
                    'file/exe',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('sulu_media.file_validator');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.file_validator',
            'setMaxFileSize',
            ['16MB']
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.file_validator',
            'setBlockedMimeTypes',
            [['file/exe']]
        );
    }

    public function testConfigureFileValidatorWithDeprecatedOptions(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load([
            'format_manager' => [
                'blocked_file_types' => [
                    'file/exe',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('sulu_media.file_validator');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.file_validator',
            'setBlockedMimeTypes',
            [['file/exe']]
        );
    }

    public function testConfigureFileValidatorWithDeprecatedAndCorrectOptions(): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->load([
            'format_manager' => [
                'blocked_file_types' => [
                    'file/exe',
                ],
            ],
            'upload' => [
                'blocked_file_types' => [
                    'image/jpeg',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('sulu_media.file_validator');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.file_validator',
            'setBlockedMimeTypes',
            [['image/jpeg']]
        );
    }

    public function testLoadFlysystemV3Storage(): void
    {
        if (!\interface_exists(FilesystemOperator::class)) {
            $this->markTestSkipped('Requires league/flysystem 3.x.');
        }

        $this->executableFinder->find(Argument::any())->willReturn(null);
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.project_dir', '/project');

        $this->load(['storage' => ['flysystem_service' => 'media.storage', 'segments' => 20]]);

        $this->assertContainerBuilderHasParameter('sulu_media.media.storage', 'flysystem_v3');
        $this->assertContainerBuilderHasService('sulu_media.storage', FlysystemV3Storage::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('sulu_media.storage', 0, new Reference('media.storage'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('sulu_media.storage', 1, new Reference('flysystem.adapter.media.storage'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('sulu_media.storage', 2, 20);
        $this->assertContainerBuilderHasAlias(StorageInterface::class, 'sulu_media.storage');
        $this->assertContainerBuilderNotHasService('sulu_media.storage.local');
    }

    public function testLoadFlysystemV3StorageWithoutFlysystem(): void
    {
        if (\interface_exists(FilesystemOperator::class)) {
            $this->markTestSkipped('Requires league/flysystem 1.x.');
        }

        $this->executableFinder->find(Argument::any())->willReturn(null);
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.project_dir', '/project');

        $this->expectException(InvalidConfigurationException::class);

        $this->load(['storage' => ['flysystem_service' => 'media.storage']]);
    }

    public function testLoadLocalStorageStaysDefault(): void
    {
        $this->executableFinder->find(Argument::any())->willReturn(null);
        $this->container->setParameter('kernel.bundles', []);

        $this->load();

        $this->assertContainerBuilderHasParameter('sulu_media.media.storage', 'local');
        $this->assertContainerBuilderHasAlias('sulu_media.storage', 'sulu_media.storage.local');
    }
}
