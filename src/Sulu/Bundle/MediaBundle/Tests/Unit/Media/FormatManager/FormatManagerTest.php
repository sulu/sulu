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
use Symfony\Component\ErrorHandler\BufferingLogger;

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

    private BufferingLogger $logger;

    protected function setUp(): void
    {
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->formatCache = $this->prophesize(FormatCacheInterface::class);
        $this->imageConverter = $this->prophesize(ImageConverterInterface::class);
        $this->logger = new BufferingLogger();

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
            mediaRepository: $this->mediaRepository->reveal(),
            formatCache: $this->formatCache->reveal(),
            converter: $this->imageConverter->reveal(),
            saveImage: 'true',
            responseHeaders: [],
            formats: $this->formats,
            logger: $this->logger
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

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 1)
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();
        $this->imageConverter->convert($fileVersion, '640x480', 'gif')->willReturn("\x47\x49\x46\x38image-content")->shouldBeCalled();

        $this->formatCache->save(
            "\x47\x49\x46\x38image-content",
            1,
            'dummy.gif',
            '640x480'
        )
            ->willReturn(null)
            ->shouldBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.gif', 1);

        $this->assertSame(200, $result->getStatusCode());
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

        $this->mediaRepository->findMediaByIdForRendering(
            1,
            '640x480',
            1
        )
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();
        $this->imageConverter->convert($fileVersion, '640x480', 'jpg')->willReturn('image-content')->shouldBeCalled();

        $this->formatCache->save(
            'image-content',
            1,
            'dummy.jpg',
            '640x480'
        )
            ->willReturn(null)
            ->shouldBeCalled()
        ;

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.jpg', 1);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertEquals('image-content', $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testReturnNewFileVersion(): void
    {
        $media = new Media();
        $reflection = new \ReflectionClass(\get_class($media));
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($media, 1);

        $file = new File();
        $file->setVersion(2);
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('dummy.gif');
        $fileVersion->setMimeType('image/gif');
        $fileVersion->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion);
        $fileVersion2 = new FileVersion();
        $fileVersion2->setVersion(2);
        $fileVersion2->setName('test.gif');
        $fileVersion2->setMimeType('image/gif');
        $fileVersion2->setStorageOptions(['a' => 'b2']);
        $file->addFileVersion($fileVersion2);
        $media->addFile($file);

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 1)
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::cetera())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();
        $this->formatCache->getMediaUrl(Argument::cetera())->will(function($args) {
            return '/' . $args[2] . '/' . $args[0] . '-' . $args[1] . '?v=' . $args[3] . '-' . $args[4];
        })->shouldBeCalled();
        $this->imageConverter->convert(Argument::cetera())->shouldNotBeCalled();
        $this->formatCache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.gif', 1);

        $this->assertSame(301, $result->getStatusCode());
        $this->assertSame('/640x480/1-test.gif?v=2-0', $result->headers->get('location'));
    }

    public function testReturn404NoFileNameMatch(): void
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

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 1)
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::cetera())->shouldNotBeCalled();
        $this->formatCache->getMediaUrl(Argument::cetera())->shouldNotBeCalled();
        $this->imageConverter->convert(Argument::cetera())->shouldNotBeCalled();
        $this->formatCache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'other.gif', 1);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testReturnNewFileVersionWebp(): void
    {
        $media = new Media();
        $reflection = new \ReflectionClass(\get_class($media));
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($media, 1);

        $file = new File();
        $file->setVersion(2);
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('dummy.gif');
        $fileVersion->setMimeType('image/gif');
        $fileVersion->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion);
        $fileVersion2 = new FileVersion();
        $fileVersion2->setVersion(2);
        $fileVersion2->setName('test.gif');
        $fileVersion2->setMimeType('image/gif');
        $fileVersion2->setStorageOptions(['a' => 'b2']);
        $file->addFileVersion($fileVersion2);
        $media->addFile($file);

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 1)
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::cetera())->willReturn(['jpg', 'png', 'gif', 'webp'])->shouldBeCalled();
        $this->formatCache->getMediaUrl(Argument::cetera())->will(function($args) {
            return '/' . $args[2] . '/' . $args[0] . '-' . $args[1] . '?v=' . $args[3] . '-' . $args[4];
        })->shouldBeCalled();
        $this->imageConverter->convert(Argument::cetera())->shouldNotBeCalled();
        $this->formatCache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.webp', 1);

        $this->assertSame(301, $result->getStatusCode());
        $this->assertSame('/640x480/1-test.webp?v=2-0', $result->headers->get('location'));
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

    public function testMissingExtension(): void
    {
        $result = $this->formatManager->returnImage(1, '640x480', 'dummy', 1);

        $this->assertSame(404, $result->getStatusCode());
        $logs = $this->logger->cleanLogs();
        $this->assertIsArray($logs[0]);
        $this->assertCount(1, $logs);
        $this->assertArrayHasKey(1, $logs[0]);
        $this->assertSame('No `extension` was found in the url "dummy".', $logs[0][1]);
    }

    public function testMediaNotFound(): void
    {
        $result = $this->formatManager->returnImage(666, '640x480', 'dummy.jpg', 1);

        $this->assertSame(404, $result->getStatusCode());
        $logs = $this->logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertIsArray($logs[0]);
        $this->assertArrayHasKey(1, $logs[0]);
        $this->assertSame('Media with id "666" was not found.', $logs[0][1]);
    }

    public function testFileVersionNotFound(): void
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

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 17)
            ->willReturn($media)
            ->shouldBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.gif', 17);

        $this->assertSame(404, $result->getStatusCode());
        $logs = $this->logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertIsArray($logs[0]);
        $this->assertArrayHasKey(1, $logs[0]);
        $this->assertSame('Requested FileVersion "17" for media with id "1" was not found.', $logs[0][1]);
    }

    public function testFileVersionNameNotFound(): void
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
        $fileVersion->setName('foo.gif');
        $fileVersion->setMimeType('image/gif');
        $fileVersion->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 1)
            ->willReturn($media)
            ->shouldBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.gif', 1);

        $this->assertSame(404, $result->getStatusCode());
        $logs = $this->logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertIsArray($logs[0]);
        $this->assertArrayHasKey(1, $logs[0]);
        $this->assertSame('FileVersion "1" for media with id "1" was not found.', $logs[0][1]);
    }

    public function testImageFormatNotFound(): void
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

        $fileVersion2 = new FileVersion();
        $fileVersion2->setVersion(17);
        $fileVersion2->setName('dummy.gif');
        $fileVersion2->setMimeType('image/gif');
        $fileVersion2->setStorageOptions(['a' => 'b']);
        $file->addFileVersion($fileVersion2);

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 17)
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.gif', 17);

        $this->assertSame(404, $result->getStatusCode());
        $logs = $this->logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertIsArray($logs[0]);
        $this->assertArrayHasKey(1, $logs[0]);
        $this->assertSame('Image format "640x480.gif" was not found for media with id "1".', $logs[0][1]);
    }

    public function testImageFormatNotSupported(): void
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

        $this->mediaRepository->findMediaByIdForRendering(1, '640x480', 1)
            ->willReturn($media)
            ->shouldBeCalled();

        $this->imageConverter->getSupportedOutputImageFormats(Argument::any())->willReturn(['jpg', 'png', 'gif'])->shouldBeCalled();

        $result = $this->formatManager->returnImage(1, '640x480', 'dummy.heic', 1);

        $this->assertSame(404, $result->getStatusCode());
        $logs = $this->logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertIsArray($logs[0]);
        $this->assertArrayHasKey(1, $logs[0]);
        $this->assertSame('Image format "heic" is not supported. Supported image formats are: "jpg, png, gif"', $logs[0][1]);
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
