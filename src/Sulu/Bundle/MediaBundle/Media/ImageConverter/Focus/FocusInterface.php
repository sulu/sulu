<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus;

use Imagine\Image\ImageInterface;

/**
 * Defines the interface for cropping the image to the given format with the defined focus point in mind.
 */
interface FocusInterface
{
    /**
     * Crops the given image based on a set focus point.
     *
     * @param ImageInterface $image The image to crop
     * @param int $x The x coordinate of the focus point
     * @param int $y The y coordinate of the focuse point
     * @param int $width The desired width of the resulting image
     * @param int $height The desired height of the resulting image
     *
     * @return ImageInterface
     */
    public function focus(ImageInterface $image, $x, $y, $width, $height);
}
