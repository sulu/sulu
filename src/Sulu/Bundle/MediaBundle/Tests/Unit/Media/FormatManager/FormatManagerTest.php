<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;

class FormatManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var FormatCacheInterface
     */
    private $formatCache;

    /**
     * @var ImageConverterInterface
     */
    private $imageConverter;

    /**
     * @var array
     */
    private $formats;

    /**
     * @var string[]
     */
    private $supportedMimeTypes;

    /**
     * @var FormatManager
     */
    private $formatManager;

    protected function setUp()
    {
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->formatCache = $this->prophesize(FormatCacheInterface::class);
        $this->imageConverter = $this->prophesize(ImageConverterInterface::class);
        $this->supportedMimeTypes = ['image/*', 'video/*'];
        $this->formats = [
            '640x480' => [
                'key' => '640x480',
                'meta' => [
                    'title' => [
                        'en' => 'My image format for testing',
                        'de' => 'Mein Bildformat zum Testen',
                    ],
                ],
                'scale' => [
                    'x' => 640,
                    'y' => 480,
                    'mode' => 'outbound',
                ],
                'transformations' => [],
                'options' => [
                    'jpeg_quality' => 70,
                    'png_compression_level' => 6,
                ],
            ],
            '50x50' => [
                'key' => '50x50',
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => 640,
                    'y' => 480,
                    'mode' => 'outbound',
                ],
                'transformations' => [],
                'options' => [
                    'jpeg_quality' => 70,
                    'png_compression_level' => 6,
                ],
            ],
        ];

        $this->formatManager = new FormatManager(
            $this->mediaRepository->reveal(),
            $this->formatCache->reveal(),
            $this->imageConverter->reveal(),
            true,
            [],
            $this->formats,
            $this->supportedMimeTypes
        );
    }

    public function testReturnImage()
    {
        $media = new Media();
        $reflection = new \ReflectionClass(get_class($media));
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($media, 1);

        $file = new File();
        $file->setVersion(1);
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('dummy.gif');
        $fileVersion->setMimeType('image/gif');
        $fileVersion->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480')->willReturn($media);

        $this->imageConverter->convert($fileVersion, '640x480')->willReturn("\x47\x49\x46\x38image-content");

        $this->formatCache->save(
            "\x47\x49\x46\x38image-content",
            1,
            'dummy.gif',
            ['a' => 'b'],
            '640x480'
        )->willReturn(null);

        $result = $this->formatManager->returnImage(1, '640x480');

        $this->assertEquals("\x47\x49\x46\x38image-content", $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testReturnImageWithVideo()
    {
        $media = new Media();
        $reflection = new \ReflectionClass(get_class($media));
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($media, 1);

        $file = new File();
        $file->setVersion(1);
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('dummy.m4v');
        $fileVersion->setMimeType('video/x-m4v');
        $fileVersion->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480')->willReturn($media);

        $this->imageConverter->convert($fileVersion, '640x480')->willReturn('image-content');

        $this->formatCache->save(
            'image-content',
            1,
            'dummy.jpg',
            ['a' => 'b'],
            '640x480'
        )->willReturn(null);

        $result = $this->formatManager->returnImage(1, '640x480');

        $this->assertEquals('image-content', $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetFormats()
    {
        $this->formatCache->getMediaUrl(1, 'dummy.gif', ['a' => 'b'], '50x50', 1, 2)->willReturn('/50x50/my-url.gif');
        $this->formatCache->getMediaUrl(1, 'dummy.gif', ['a' => 'b'], '640x480', 1, 2)->willReturn('/640x480/my-url.gif');

        $result = $this->formatManager->getFormats(
            1,
            'dummy.gif',
            ['a' => 'b'],
            1,
            2,
            'image/gif'
        );

        $this->assertEquals(
            [
                '640x480' => '/640x480/my-url.gif',
                '50x50' => '/50x50/my-url.gif',
            ],
            $result
        );
    }

    public function testGetFormatsNotSupportedMimeType()
    {
        $this->formatCache->getMediaUrl(1, 'dummy.mp3', ['a' => 'b'], '640x480', 1, 2)->shouldNotBeCalled();

        $result = $this->formatManager->getFormats(
            1,
            'dummy.mp3',
            ['a' => 'b'],
            1,
            2,
            'mp3'
        );

        $this->assertEquals([], $result);
    }

    public function testGetFormatDefinition()
    {
        $format = $this->formatManager->getFormatDefinition('640x480', 'en', ['my-option' => 'my-value']);

        $this->assertEquals('640x480', $format['key']);
        $this->assertEquals('My image format for testing', $format['title']);
        $this->assertEquals(['x' => 640, 'y' => 480, 'mode' => 'outbound'], $format['scale']);
        $this->assertEquals(['my-option' => 'my-value'], $format['options']);
    }

    public function testGetFormatDefinitionNotExistingTitle()
    {
        $format = $this->formatManager->getFormatDefinition('50x50', 'en');
        $this->assertEquals('50x50', $format['title']);
    }

    public function testGetFormatDefinitionNotExistingLocale()
    {
        $format = $this->formatManager->getFormatDefinition('640x480', 'it');
        $this->assertEquals('My image format for testing', $format['title']);
    }

    public function testGetFormatDefinitions()
    {
        $formats = $this->formatManager->getFormatDefinitions('de');

        $this->assertEquals(
            [
                'key' => '640x480',
                'title' => 'Mein Bildformat zum Testen',
                'scale' => [
                    'x' => 640,
                    'y' => 480,
                    'mode' => 'outbound',
                ],
                'options' => null,
            ],
            $formats['640x480']
        );

        $this->assertEquals(
            [
                'key' => '50x50',
                'title' => '50x50',
                'scale' => [
                    'x' => 640,
                    'y' => 480,
                    'mode' => 'outbound',
                ],
                'options' => null,
            ],
            $formats['50x50']
        );
    }

    public function testPurge()
    {
        $this->formatCache->purge(1, 'test.jpg', null)->shouldBeCalled();

        $this->formatManager->purge(1, 'test.jpg', 'image/jpeg', null);
    }

    public function testPurgeUppercaseExtension()
    {
        $this->formatCache->purge(1, 'test.jpg', null)->shouldBeCalled();

        $this->formatManager->purge(1, 'test.JPG', 'image/jpeg', null);
    }
}
