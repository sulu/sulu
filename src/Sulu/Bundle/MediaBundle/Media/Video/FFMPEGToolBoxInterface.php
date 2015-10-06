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


interface FFMPEGToolBoxInterface
{
    /**
     * returns the duration of a video.
     *
     * @param $video
     *
     * @return string
     */
    public function getVideoDuration($video);
}
