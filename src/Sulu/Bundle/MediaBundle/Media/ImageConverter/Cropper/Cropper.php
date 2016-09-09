<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

/**
 * The class represents a cropper of an image, according to the interface it implements.
 */
class Cropper implements CropperInterface
{
    /**
     * {@inheritdoc}
     */
    public function crop(ImageInterface $image, $x, $y, $width, $height)
    {
        $point = new Point($x, $y);
        $box = new Box($width, $height);

        $image->crop($point, $box);

        return $image;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(ImageInterface $image, $x, $y, $width, $height, array $format)
    {
        return $this->isInsideImage($image, $x, $y, $width, $height)
            && $this->isNotSmallerThanFormat($width, $height, $format);
    }

    /**
     * Returns true iff the cropping does not exceed the image borders.
     *
     * @param ImageInterface $image
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     *
     * @return bool
     */
    private function isInsideImage(ImageInterface $image, $x, $y, $width, $height)
    {
        if ($x < 0 || $y < 0) {
            return false;
        }
        if ($x + $width > $image->getSize()->getWidth()) {
            return false;
        }
        if ($y + $height > $image->getSize()->getHeight()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true iff the crop is greater or equal to the size of a given format.
     *
     * @param $width
     * @param $height
     * @param array $format
     *
     * @return bool
     */
    private function isNotSmallerThanFormat($width, $height, array $format)
    {
        if (isset($format['scale']['x']) && $width < $format['scale']['x']) {
            return false;
        }
        if (isset($format['scale']['y']) && $height < $format['scale']['y']) {
            return false;
        }

        return true;
    }
}
