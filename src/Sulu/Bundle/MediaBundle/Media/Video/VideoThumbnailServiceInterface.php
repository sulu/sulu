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
     *
     * @return bool
     */
    public function generate($file, $time, $destination);

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
    );
}
