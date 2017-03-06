<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\SuluMediaExtension;

class SuluMediaExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SuluMediaExtension(),
        ];
    }

    public function testLoad()
    {
        $this->container->setParameter('kernel.root_dir', __DIR__);

        $this->load();

        $this->assertContainerBuilderHasService('sulu_media.media_manager');
        $this->assertContainerBuilderHasParameter('sulu_media.format_manager.response_headers', [
            'Expires' => '+1 month',
            'Pragma' => 'public',
            'Cache-Control' => 'public',
        ]);
        $this->assertContainerBuilderHasParameter('sulu_media.search.default_image_format', 'sulu-170x170');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.path', '%kernel.root_dir%/../uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.segments', 10);
        $this->assertContainerBuilderHasParameter('sulu_media.collection.type.default', [
            'id' => 1,
        ]);
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.save_image', true);
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.path', '%kernel.root_dir%/../web/uploads/media');
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
