<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Video;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\FFMpeg;

/**
 * Service to generate thumbnails from videos.
 */
class VideoThumbnailService implements VideoThumbnailServiceInterface
{
    /**
     * @var FFMpeg
     */
    protected $ffmpeg;

    public function __construct(
        FFMpeg $ffmpeg
    ) {
        $this->ffmpeg = $ffmpeg;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($file, $time, $destination)
    {
        $destination = $this->normalizeFilename($destination);

        try {
            $video = $this->ffmpeg->open($file);

            $timecode = TimeCode::fromString($time);

            $frame = $video->frame($timecode);
            $frame->save($destination);
        } catch (InvalidArgumentException $e) {
            // there will be no image file - so nothing to do here
        }

        return file_exists($destination);
    }

    /**
     * {@inheritdoc}
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
