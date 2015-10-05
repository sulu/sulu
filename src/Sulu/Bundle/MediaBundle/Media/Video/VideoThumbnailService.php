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

class VideoThumbnailService
{
    /** @var string */
    protected $ffmpeg;

    public function __construct(
        $ffmpeg
    ) {
        $this->ffmpeg = $ffmpeg;
    }

    /**
     * @param string $file
     * @param string $time
     * @param string $destination
     * @param int $width
     * @param int $height
     * @param bool $allowUpscaling
     *
     * @return bool
     */
    public function generate($file, $time, $destination, $width = -1, $height = -1, $allowUpscaling = true)
    {
        if ($this->ffmpeg !== null) {
            $destination = $this->normalizeFilename($destination);

            $size = '';
            if ($width !== -1 || $height !== -1) {
                $size = sprintf('-vf "scale=%s:%s"', $width, $height);
                if (!$allowUpscaling) {
                   $size = sprintf('-vf "scale=min(iw\,%s):min(iw\,%s)"', $width, $height);
                }
            }

            $command = sprintf(
                '%s -i %s %s -ss "%s" -vframes 1 -y %s',
                $this->ffmpeg,
                $file,
                $size,
                $time,
                $destination
            );
            exec($command, $rValue, $rError);

            return $rError == 0;
        }

        return false;
    }

    /**
     * @param string $video
     * @param array $times
     * @param string $destinationPath
     * @param int $width
     * @param int $height
     * @param bool $allowUpscaling
     *
     * @return array|bool
     */
    public function batchGenerate(
        $video,
        array $times,
        $destinationPath,
        $width = -1,
        $height = -1,
        $allowUpscaling = true
    ) {
        if ($this->ffmpeg !== null) {
            $failed = [];
            foreach ($times as $time) {
                $filename = $destinationPath . DIRECTORY_SEPARATOR . $time . '.jpg';
                $success = $this->generate($video, $time, $filename, $width, $height, $allowUpscaling);

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
