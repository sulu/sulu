<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropping;

use Imagine\Image\ImageInterface;

/**
 * Defines a cropping on an image.
 */
interface CroppingInterface
{
    /**
     * Crops an image according to the given parameters.
     *
     * @param ImageInterface $image The image to crop
     * @param int $x The x value of the point from where the crop area starts
     * @param int $y The y value of the point from where the crop area starts
     * @param int $width The width of the crop area
     * @param int $width The height of the crop area
     *
     * @return ImageInterface
     */
    public function crop(ImageInterface $image, $x, $y, $width, $height);
}
