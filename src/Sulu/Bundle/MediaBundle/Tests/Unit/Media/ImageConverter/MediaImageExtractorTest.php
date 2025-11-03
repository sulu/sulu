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

    public function testExtractUnseekableResourceMimeTypeGiven(): void
    {
        $resource = $this->createUnseekableResource();

        $this->assertSame(
            $resource,
            $this->mediaImageExtractor->extract($resource, 'image/jpeg')
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
