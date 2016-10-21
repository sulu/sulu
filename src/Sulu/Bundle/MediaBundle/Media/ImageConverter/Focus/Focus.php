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

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class Focus implements FocusInterface
{
    const FOCUS_LEFT = 0;

    const FOCUS_RIGHT = 2;

    const FOCUS_TOP = 0;

    const FOCUS_BOTTOM = 2;

    /**
     * @param ImageInterface $image The image to crop
     * @param int $x The x coordinate of the focus point
     * @param int $y The y coordinate of the focuse point
     * @param int $width The desired width of the resulting image
     * @param int $height The desired height of the resulting image
     *
     * @return ImageInterface
     */
    public function focus(ImageInterface $image, $x, $y, $width, $height)
    {
        $imageSize = $image->getSize();

        $currentRatio = $imageSize->getWidth() / $imageSize->getHeight();
        $targetRatio = $currentRatio;
        if ($width !== null && $height !== null) {
            $targetRatio = $width / $height;
        }

        if ($targetRatio < $currentRatio) {
            $height = $imageSize->getHeight();
            $width = $targetRatio * $height;

            $cropY = 0;

            switch ($x) {
                case static::FOCUS_LEFT:
                    $cropX = 0;
                    break;
                case static::FOCUS_RIGHT:
                    $cropX = $imageSize->getWidth() - $width;
                    break;
                default:
                    $cropX = ($imageSize->getWidth() - $width) / 2;
            }
        } else {
            $width = $imageSize->getWidth();
            $height = $width / $targetRatio;

            $cropX = 0;

            switch ($y) {
                case static::FOCUS_TOP:
                    $cropY = 0;
                    break;
                case static::FOCUS_BOTTOM:
                    $cropY = $imageSize->getHeight() - $height;
                    break;
                default:
                    $cropY = ($imageSize->getHeight() - $height) / 2;
            }
        }

        return $image->crop(new Point($cropX, $cropY), new Box($width, $height));
    }
}
