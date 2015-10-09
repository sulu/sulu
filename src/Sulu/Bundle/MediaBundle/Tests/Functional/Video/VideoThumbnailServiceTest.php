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


use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailService;

class VideoThumbnailServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $files = [];

    /** @var string */
    protected $basePath;

    /** @var string */
    protected $video;

    /** @var VideoThumbnailService */
    protected $videoThumbnailService;

    /** @var string */
    protected $ffmpeg;

    protected function setUp()
    {
        parent::setUp();

        $this->basePath = __DIR__ . '/../../../bin/downloads/';

        $this->ffmpeg = 'ffmpeg -loglevel panic';
        $this->video = $this->basePath . 'BigBuckBunny_320x180.mp4';
        $this->videoThumbnailService = new VideoThumbnailService($this->ffmpeg);
    }

    public function testGenerate()
    {
        $destination1 = $this->basePath . 'image_1.jpg';
        $destination2 = $this->basePath . 'image_2.jpg';

        // check if 2 generated thumbnails are equal
        $result = $this->videoThumbnailService->generate($this->video, 3, $destination1);

        $this->assertTrue($result, 'Thumbnail generation 1 failed');
        $this->assertFileExists($destination1, 'Thumbnail file 1 does not exist');

        $this->files[] = $destination1;

        $result = $this->videoThumbnailService->generate($this->video, 3, $destination2);
        $this->assertTrue($result, 'Thumbnail generation 2 failed');
        $this->assertFileExists($destination2, 'Thumbnail file 2 does not exist');

        $this->files[] = $destination2;

        $this->assertEquals(md5_file($destination1), md5_file($destination2), 'Thumbnails do not match');

        // check if 2 generated thumbnails are not equal
        $result = $this->videoThumbnailService->generate($this->video, 3, $destination1);

        $this->assertTrue($result, 'Thumbnail generation 1 failed');
        $this->assertFileExists($destination1, 'Thumbnail file 1 does not exist');

        $this->files[] = $destination1;

        $result = $this->videoThumbnailService->generate($this->video, 5, $destination2);
        $this->assertTrue($result, 'Thumbnail generation 2 failed');
        $this->assertFileExists($destination2, 'Thumbnail file 2 does not exist');

        $this->files[] = $destination2;

        $this->assertNotEquals(md5_file($destination1), md5_file($destination2), 'Thumbnails should not match');
    }

    public function testBatchGenerate()
    {
        $times = ['1', '3', '8', '10', '1:15', '1:22', '3:00:00'];

        // check if 2 generated thumbnails are equal
        $failed = $this->videoThumbnailService->batchGenerate($this->video, $times, $this->basePath);

        // thumbnail at time 3:00:00 must fail - the video is not long enough
        $this->assertCount(1, $failed, 'thumbnail batch generation failed');

        foreach ($times as $time) {
            $file = $this->basePath . str_replace(':', '.', $time) . '.jpg';
            $this->files[] =  $file;
        }

        foreach ($this->files as $file) {
            if ($file === $this->basePath . '3.00.00.jpg') {
                $this->assertFileNotExists($file);
            } else {
                $this->assertFileExists($file);
            }
        }
    }

    protected function tearDown()
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }
}
