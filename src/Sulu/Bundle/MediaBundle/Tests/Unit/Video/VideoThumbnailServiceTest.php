<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Video;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Frame;
use FFMpeg\Media\Video;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailService;

class VideoThumbnailServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Video
     */
    protected $video;

    /**
     * @var VideoThumbnailService
     */
    protected $videoThumbnailService;

    /**
     * @var Frame
     */
    protected $frame;

    /**
     * @var FFMpeg
     */
    protected $ffmpeg;

    protected function setUp()
    {
        parent::setUp();

        $this->ffmpeg = $this->prophesize(FFMpeg::class);
        $this->video = $this->prophesize(Video::class);
        $this->frame = $this->prophesize(Frame::class);

        $this->videoThumbnailService = new VideoThumbnailService($this->ffmpeg->reveal());
    }

    public function testGenerate()
    {
        $timecode = TimeCode::fromString('00:00:00:01');

        $this->ffmpeg->open('1.mp4')->willReturn($this->video->reveal())->shouldBeCalled();
        $this->video->frame($timecode)->willReturn($this->frame->reveal())->shouldBeCalled();
        $this->frame->save('1.jpg')->shouldBeCalled();

        $this->videoThumbnailService->generate('1.mp4', '00:00:00:01', '1.jpg');
    }

    public function testBatchGenerate()
    {
        $times = ['00:00:00:01', '00:00:00:11', '00:00:00:21'];

        foreach ($times as $time) {
            $timecode = TimeCode::fromString($time);

            $this->ffmpeg->open('1.mp4')->willReturn($this->video->reveal())->shouldBeCalled();
            $this->video->frame($timecode)->willReturn($this->frame->reveal())->shouldBeCalled();
            $this->frame->save(str_replace(':', '.', DIRECTORY_SEPARATOR . $time . '.jpg'))->shouldBeCalled();
        }

        $this->videoThumbnailService->batchGenerate('1.mp4', $times, '');
    }
}
