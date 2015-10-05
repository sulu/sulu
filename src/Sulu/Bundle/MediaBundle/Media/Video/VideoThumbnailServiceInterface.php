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

interface VideoThumbnailServiceInterface
{
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
    public function generate($file, $time, $destination, $width = -1, $height = -1, $allowUpscaling = true);

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
    );
}
