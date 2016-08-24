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

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

/**
 * The class represents a cropping of an image, according to the interface it implements.
 */
class Cropping implements CroppingInterface
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
}
