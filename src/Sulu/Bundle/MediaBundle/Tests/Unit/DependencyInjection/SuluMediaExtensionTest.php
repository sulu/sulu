<?php
/*
 * This file is part of the Sulu CMS.
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
        return array(
            new SuluMediaExtension()
        );
    }

    public function testLoad()
    {
        $this->load();

        $this->assertContainerBuilderHasService('sulu_media.media_manager');
        $this->assertContainerBuilderHasParameter('sulu_media.format_manager.response_headers', array(
            'Expires' => '+1 month',
            'Pragma' => 'public',
            'Cache-Control' => 'public'
        ));
        $this->assertContainerBuilderHasParameter('sulu_media.search.default_image_format', '170x170');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.path', '%kernel.root_dir%/../uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.media.storage.local.segments', 10);
        $this->assertContainerBuilderHasParameter('sulu_media.collection.type.default', array(
            'id' => 1
        ));
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.save_image', 'true');
        $this->assertContainerBuilderHasParameter('sulu_media.format_cache.path', '%kernel.root_dir%/../web/uploads/media');
        $this->assertContainerBuilderHasParameter('sulu_media.image.command.prefix', 'image.converter.prefix.');
        $this->assertContainerBuilderHasParameter('sulu_media.media.blocked_file_types', array('file/exe'));
        $this->assertContainerBuilderHasParameter('ghost_script.path', 'gs');
        $this->assertContainerBuilderHasParameter('sulu_media.format_manager.mime_types',  array(
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/png',
            'image/bmp',
            'image/svg+xml',
            'image/vnd.adobe.photoshop',
            'application/pdf',
        ));
        $this->assertContainerBuilderHasParameter('sulu_media.media.types', array(
            array(
                'type' => 'document',
                'mimeTypes' => array('*')
            ),
            array(
                'type' => 'image',
                'mimeTypes' => array('image/*')
            ),
            array(
                'type' => 'video',
                'mimeTypes' => array('video/*')
            ),
            array(
                'type' => 'audio',
                'mimeTypes' => array('audio/*')
            )
        ));
    }
}
