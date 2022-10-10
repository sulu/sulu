<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;

class FormatManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MediaRepositoryInterface>
     */
    private $mediaRepository;

    /**
     * @var ObjectProphecy<FormatCacheInterface>
     */
    private $formatCache;

    /**
     * @var ObjectProphecy<ImageConverterInterface>
     */
    private $imageConverter;

    /**
     * @var array
     */
    private $formats;

    /**
     * @var FormatManager
     */
    private $formatManager;

    protected function setUp(): void
    {
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->formatCache = $this->prophesize(FormatCacheInterface::class);
        $this->imageConverter = $this->prophesize(ImageConverterInterface::class);
        $this->formats = [
            '640x480' => [
                'internal' => false,
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
                'internal' => true,
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
            $this->formats
        );
    }

    public function testReturnImage(): void
    {
        $media = new Media();
        $reflection = new \ReflectionClass(\get_class($media));
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

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();
        $this->imageConverter->convert($fileVersion, '640x480', 'gif')->willReturn("\x47\x49\x46\x38image-content");

        $this->formatCache->save(
            "\x47\x49\x46\x38image-content",
            1,
            'dummy.gif',
            '640x480'
        )->willReturn(null);

        $result = $this->formatManager->returnImage(1, '640x480', 'test.gif');

        $this->assertEquals("\x47\x49\x46\x38image-content", $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testReturnImageWithVideo(): void
    {
        $media = new Media();
        $reflection = new \ReflectionClass(\get_class($media));
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

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();
        $this->imageConverter->convert($fileVersion, '640x480', 'jpg')->willReturn('image-content');

        $this->formatCache->save(
            'image-content',
            1,
            'dummy.jpg',
            '640x480'
        )->willReturn(null);

        $result = $this->formatManager->returnImage(1, '640x480', 'test.jpg');

        $this->assertEquals('image-content', $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetFormats(): void
    {
        $this->formatCache->getMediaUrl(1, 'dummy.gif', '50x50', 1, 2)->willReturn('/50x50/my-url.gif');
        $this->formatCache->getMediaUrl(1, 'dummy.jpg', '50x50', 1, 2)->willReturn('/50x50/my-url.jpg');
        $this->formatCache->getMediaUrl(1, 'dummy.gif', '640x480', 1, 2)->willReturn('/640x480/my-url.gif');
        $this->formatCache->getMediaUrl(1, 'dummy.jpg', '640x480', 1, 2)->willReturn('/640x480/my-url.jpg');

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['gif', 'jpg']);
        $result = $this->formatManager->getFormats(
            1,
            'dummy.gif',
            1,
            2,
            'image/gif'
        );

        $this->assertEquals(
            [
                '640x480' => '/640x480/my-url.gif',
                '640x480.gif' => '/640x480/my-url.gif',
                '640x480.jpg' => '/640x480/my-url.jpg',
                '50x50' => '/50x50/my-url.gif',
                '50x50.gif' => '/50x50/my-url.gif',
                '50x50.jpg' => '/50x50/my-url.jpg',
            ],
            $result
        );
    }

    public function testGetFormatsNotSupportedMimeType(): void
    {
        $this->formatCache->getMediaUrl(1, 'dummy.mp3', '640x480', 1, 2)->shouldNotBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn([])->shouldBeCalled();
        $result = $this->formatManager->getFormats(
            1,
            'dummy.mp3',
            1,
            2,
            'mp3'
        );

        $this->assertEquals([], $result);
    }

    public function testGetFormatsWithMultipleDotsInFilename(): void
    {
        $this->formatCache->getMediaUrl(1, 'dummy.bak.jpg', '640x480', 1, 2)->shouldBeCalled();
        $this->formatCache->getMediaUrl(1, 'dummy.bak.jpg', '50x50', 1, 2)->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg'])->shouldBeCalled();

        $this->formatManager->getFormats(
            1,
            'dummy.bak.jpg',
            1,
            2,
            'jpg'
        );
    }

    public function testGetFormatDefinition(): void
    {
        $format = $this->formatManager->getFormatDefinition('640x480', 'en', ['my-option' => 'my-value']);

        $this->assertEquals('640x480', $format['key']);
        $this->assertEquals(false, $format['internal']);
        $this->assertEquals('My image format for testing', $format['title']);
        $this->assertEquals(['x' => 640, 'y' => 480, 'mode' => 'outbound'], $format['scale']);
    }

    public function testGetFormatDefinitionNotExistingTitle(): void
    {
        $format = $this->formatManager->getFormatDefinition('50x50', 'en');
        $this->assertEquals('50x50', $format['title']);
    }

    public function testGetFormatDefinitionNotExistingLocale(): void
    {
        $format = $this->formatManager->getFormatDefinition('640x480', 'it');
        $this->assertEquals('My image format for testing', $format['title']);
    }

    public function testGetFormatDefinitions(): void
    {
        $formats = $this->formatManager->getFormatDefinitions('de');

        $this->assertEquals(
            [
                'internal' => false,
                'key' => '640x480',
                'title' => 'Mein Bildformat zum Testen',
                'scale' => [
                    'x' => 640,
                    'y' => 480,
                    'mode' => 'outbound',
                ],
            ],
            $formats['640x480']
        );

        $this->assertEquals(
            [
                'internal' => true,
                'key' => '50x50',
                'title' => '50x50',
                'scale' => [
                    'x' => 640,
                    'y' => 480,
                    'mode' => 'outbound',
                ],
            ],
            $formats['50x50']
        );
    }

    public function testPurge(): void
    {
        $this->formatCache->purge(1, 'test.jpg', '640x480')->shouldBeCalled();
        $this->formatCache->purge(1, 'test.jpg', '50x50')->shouldBeCalled();
        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg'])->shouldBeCalled();

        $this->formatManager->purge(1, 'test.jpg', 'image/jpeg', null);
    }

    public function testPurgeUppercaseExtension(): void
    {
        $this->formatCache->purge(1, 'test.jpg', '640x480')->shouldBeCalled();
        $this->formatCache->purge(1, 'test.jpg', '50x50')->shouldBeCalled();
        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg'])->shouldBeCalled();

        $this->formatManager->purge(1, 'test.JPG', 'image/jpeg', null);
    }
}
