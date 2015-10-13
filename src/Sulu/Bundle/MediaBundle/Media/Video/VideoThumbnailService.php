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

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;

class VideoThumbnailService implements VideoThumbnailServiceInterface
{
    /** @var FFMpeg */
    protected $ffmpeg;

    public function __construct(
        FFMpeg $ffmpeg
    ) {
        $this->ffmpeg = $ffmpeg;
    }

    /**
     * @param string $file
     * @param string $time
     * @param string $destination
     *
     * @return bool
     */
    public function generate($file, $time, $destination)
    {
        $destination = $this->normalizeFilename($destination);

        try {
            $video = $this->ffmpeg->open($file);

            $timecode = TimeCode::fromString($time);

            $frame = $video->frame($timecode);
            $frame->save($destination);
        } catch (\Exception $e) {

        }

        return file_exists($destination);
    }

    /**
     * @param string $video
     * @param array $times
     * @param string $destinationPath
     *
     * @return array|bool
     */
    public function batchGenerate(
        $video,
        array $times,
        $destinationPath
    ) {
        if ($this->ffmpeg !== null) {
            $failed = [];
            foreach ($times as $time) {
                $filename = $destinationPath . DIRECTORY_SEPARATOR . $time . '.jpg';
                $success = $this->generate($video, $time, $filename);

                if (!$success) {
                    $failed[] = $filename;
                }
            }

            return $failed;
        }

        return false;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    protected function normalizeFilename($filename)
    {
        $filename = str_replace(':', '.', $filename);

        return $filename;
    }
}
