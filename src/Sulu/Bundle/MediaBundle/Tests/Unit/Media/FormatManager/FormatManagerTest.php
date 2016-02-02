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

use Imagine\Image\ImageInterface;
use Prophecy\Argument;
use Prophecy\Prediction\NoCallsPrediction;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;

class FormatManagerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testReturnImage()
    {
        $mediaRepository = $this->prophesize('Sulu\Bundle\MediaBundle\Entity\MediaRepository');
        $originalStorage = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface');
        $formatCache = $this->prophesize('Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface');
        $converter = $this->prophesize('Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface');
        $videoThumbnailService = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface');

        $ghostScriptPath = '';
        $saveImage = true;
        $previewMimeTypes = ['gif'];
        $responseHeaders = [];
        $formats = [
            '640x480' => [
                'name' => '640x480',
                'commands' => [
                    [
                        'action' => 'resize',
                        'parameters' => [
                            'x' => 640,
                            'y' => 480,
                        ],
                    ],
                ],
                'options' => [
                    'jpeg_quality' => 70,
                    'png_compression_level' => 6,
                ],
            ],
        ];

        $image = $this->prophesize('Imagine\Image\ImageInterface');
        $image->strip()->willReturn(null);
        $image->layers()->willReturn(null);
        $image->interlace(ImageInterface::INTERLACE_PLANE)->willReturn(null);
        $image->get('gif', $formats['640x480']['options'])->willReturn('Image-Content');

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
        $fileVersion->setMimeType('gif');
        $fileVersion->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        $mediaRepository->findMediaById(1)->willReturn($media);

        $originalStorage->load('dummy.gif', 1, ['a' => 'b'])->willReturn(
            dirname(__DIR__) . '/../../Fixtures/image/data/dummy.gif'
        );

        $converter->convert(Argument::type('string'), $formats['640x480'])->willReturn($image->reveal());

        $formatCache->save(
            Argument::type('string'),
            1,
            'dummy.gif',
            ['a' => 'b'],
            '640x480'
        )->willReturn(null);

        $formatManager = new FormatManager(
            $mediaRepository->reveal(),
            $originalStorage->reveal(),
            $formatCache->reveal(),
            $converter->reveal(),
            $videoThumbnailService->reveal(),
            $ghostScriptPath,
            $saveImage,
            $previewMimeTypes,
            $responseHeaders,
            $formats
        );

        $result = $formatManager->returnImage(1, '640x480');

        $this->assertEquals('Image-Content', $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetFormats()
    {
        $mediaRepository = $this->prophesize('Sulu\Bundle\MediaBundle\Entity\MediaRepository');
        $originalStorage = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface');
        $formatCache = $this->prophesize('Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface');
        $converter = $this->prophesize('Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface');
        $videoThumbnailService = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface');

        $ghostScriptPath = '';
        $saveImage = true;
        $previewMimeTypes = ['gif'];
        $responseHeaders = [];
        $formats = [
            '640x480' => [
                'name' => '640x480',
                'commands' => [
                    [
                        'action' => 'resize',
                        'parameters' => [
                            'x' => 640,
                            'y' => 480,
                        ],
                    ],
                ],
                'options' => [
                    'jpeg_quality' => 70,
                    'png_compression_level' => 6,
                ],
            ],
        ];

        $formatCache->getMediaUrl(1, 'dummy.gif', ['a' => 'b'], '640x480', 1)->willReturn('/my-url.gif');

        $formatManager = new FormatManager(
            $mediaRepository->reveal(),
            $originalStorage->reveal(),
            $formatCache->reveal(),
            $converter->reveal(),
            $videoThumbnailService->reveal(),
            $ghostScriptPath,
            $saveImage,
            $previewMimeTypes,
            $responseHeaders,
            $formats
        );

        $result = $formatManager->getFormats(
            1,
            'dummy.gif',
            ['a' => 'b'],
            1,
            'gif'
        );

        $this->assertEquals(['640x480' => '/my-url.gif'], $result);
    }

    public function testGetFormatsNotSupportedMimeType()
    {
        $mediaRepository = $this->prophesize('Sulu\Bundle\MediaBundle\Entity\MediaRepository');
        $originalStorage = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface');
        $formatCache = $this->prophesize('Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface');
        $converter = $this->prophesize('Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface');
        $videoThumbnailService = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface');

        $ghostScriptPath = '';
        $saveImage = true;
        $previewMimeTypes = ['gif'];
        $responseHeaders = [];
        $formats = [
            '640x480' => [
                'name' => '640x480',
                'commands' => [
                    [
                        'action' => 'resize',
                        'parameters' => [
                            'x' => 640,
                            'y' => 480,
                        ],
                    ],
                ],
                'options' => [
                    'jpeg_quality' => 70,
                    'png_compression_level' => 6,
                ],
            ],
        ];

        $formatCache->getMediaUrl(1, 'dummy.mp3', ['a' => 'b'], '640x480', 1)->should(new NoCallsPrediction());

        $formatManager = new FormatManager(
            $mediaRepository->reveal(),
            $originalStorage->reveal(),
            $formatCache->reveal(),
            $converter->reveal(),
            $videoThumbnailService->reveal(),
            $ghostScriptPath,
            $saveImage,
            $previewMimeTypes,
            $responseHeaders,
            $formats
        );

        $result = $formatManager->getFormats(
            1,
            'dummy.mp3',
            ['a' => 'b'],
            1,
            'mp3'
        );

        $this->assertEquals([], $result);
    }
}
