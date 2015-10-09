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

use Sulu\Bundle\MediaBundle\Media\Video\VideoUtils;

class VideoUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetVideoDuration()
    {
        $basePath = __DIR__ . '/../../../bin/downloads/';
        $ffmpeg = 'ffmpeg';
        $video = $basePath . 'BigBuckBunny_320x180.mp4';

        /** @var VideoUtils $videoUtils */
        $videoUtils = new VideoUtils($ffmpeg);

        $duration = $videoUtils->getVideoDuration($video);

        $this->assertEquals($duration, '00:09:56.46', 'Video duration does not match');
    }
}
