<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter;

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\MediaImageExtractor;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailServiceInterface;

class MediaImageExtractorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ImagineInterface>
     */
    private $imagine;

    /**
     * @var ObjectProphecy<VideoThumbnailServiceInterface>
     */
    private $videoThumbnail;

    /**
     * @var MediaImageExtractor
     */
    private $mediaImageExtractor;

    public function setUp(): void
    {
        $this->imagine = $this->prophesize(ImagineInterface::class);
        $this->videoThumbnail = $this->prophesize(VideoThumbnailServiceInterface::class);

        $this->mediaImageExtractor = new MediaImageExtractor(
            $this->imagine->reveal(),
            $this->videoThumbnail->reveal(),
            'gs'
        );
    }

    public function testExtractUnseekableResource(): void
    {
        $resource = $this->createUnseekableResource();

        // this test just check if "createUnseekableResource" will produce the correct unseekable resource to reproduce
        // bug with aws s3 unseekable resources: https://github.com/sulu/sulu/issues/5468
        // if this test fail the createUnseekableResource is maybe wrong
        $this->expectWarning();

        if (\PHP_VERSION_ID >= 80000) {
            $this->expectWarningMessage('mime_content_type(): Stream does not support seeking');
        } else {
            $this->expectWarningMessage('mime_content_type(): stream does not support seeking');
        }

        $this->mediaImageExtractor->extract($resource);
    }

    public function testExtractUnseekableResourceMimeTypeGiven(): void
    {
        $resource = $this->createUnseekableResource();

        $this->assertSame(
            $resource,
            $this->mediaImageExtractor->extract($resource, 'image/jpeg')
        );
    }

    public function testPsdConvertWithoutMimeType(): void
    {
        $resource = \fopen(\dirname(\dirname(\dirname(__DIR__))) . '/Fixtures/files/1x1.psd', 'r');

        $image = $this->prophesize(ImageInterface::class);
        $image->layers()->willReturn([$image->reveal()]);

        $image->get('png')
            ->shouldBeCalled()
            ->willReturn('PNG Content');

        $this->imagine->read($resource)
            ->willReturn($image->reveal())
            ->shouldBeCalled();

        $this->assertSame(
            'PNG Content',
            \stream_get_contents($this->mediaImageExtractor->extract($resource))
        );
    }

    /**
     * @return resource
     */
    private function createUnseekableResource()
    {
        $resource = \fopen('https://sulu.io/website/images/sulu.png', 'r');

        // make sure the stream is not seekable
        $this->assertFalse(\stream_get_meta_data($resource)['seekable']);

        return $resource;
    }
}
