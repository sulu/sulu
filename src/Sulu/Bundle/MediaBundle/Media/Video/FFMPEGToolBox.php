<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Video;


class FFMPEGToolBox implements FFMPEGToolBoxInterface
{
    /** @var string */
    protected $ffmpeg;

    public function __construct(
        $ffmpeg
    ) {
        $this->ffmpeg = $ffmpeg;
    }

    /**
     * returns the duration of a video.
     *
     * @param $video
     *
     * @return string
     */
    public function getVideoDuration($video)
    {
        if ($this->ffmpeg !== null) {
            $command = sprintf('%s -i "%s" 2>&1', $this->ffmpeg, $video);
            ob_start();
            passthru($command);
            $result = ob_get_contents();
            ob_end_clean();

            preg_match('/Duration: (.*?),/', $result, $matches);
            $duration = $matches[1];

            return $duration;
        }

        return;
    }
}
