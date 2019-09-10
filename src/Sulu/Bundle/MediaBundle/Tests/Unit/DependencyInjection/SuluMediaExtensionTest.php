<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\SuluMediaExtension;

class SuluMediaExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SuluMediaExtension(),
        ];
    }

    public function testLoad()
    {
        $this->container->setParameter('kernel.root_dir', __DIR__);
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
            'Cache-Control' => 'public',
        ]);
        $this->assertContainerBuilderHasParameter('sulu_media.search.default_image_format', 'sulu-100x100');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.path', '%kernel.project_dir%/var/uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.segments', 10);
        $this->assertContainerBuilderHasParameter('sulu_media.collection.type.default', [
            'id' => 1,
        ]);
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.save_image', true);
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.path', '%kernel.project_dir%/public/uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.media.blocked_file_types', ['file/exe']);
        $this->assertContainerBuilderHasParameter('sulu_media.ghost_script.path', 'gs');
        $this->assertContainerBuilderHasParameter('sulu_media.format_manager.mime_types', [
            'image/*',
            'video/*',
            'application/pdf',
        ]);
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
}
