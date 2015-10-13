<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Video;

use FFMpeg\FFMpeg;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailService;

class VideoThumbnailServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $basePath;

    /** @var string */
    protected $video;

    /** @var VideoThumbnailService */
    protected $videoThumbnailService;

    /** @var FFMpeg */
    protected $ffmpeg;

    protected function setUp()
    {
        parent::setUp();

        $this->basePath = __DIR__ . '/../../../bin/downloads/';

        $this->ffmpeg = $this->prophesize(FFMpeg::class);
        $this->video = $this->basePath . 'BigBuckBunny_320x180.mp4';
        $this->videoThumbnailService = new VideoThumbnailService($this->ffmpeg->reveal());
    }

    public function testGenerate()
    {
        $this->ffmpeg->open('1.mp4')->shouldBeCalled();

        $this->videoThumbnailService->generate('1.mp4', '00:00:00:01', '1.jpg');
    }

    public function testBatchGenerate()
    {
        $times = ['00:00:00:01', '00:00:00:11', '00:00:00:21'];

        // check if 2 generated thumbnails are equal
        $failed = $this->videoThumbnailService->batchGenerate($this->video, $times, $this->basePath);

        // thumbnail at time 3:00:00 must fail - the video is not long enough
        $this->assertCount(1, $failed, 'thumbnail batch generation failed');

    }
}
