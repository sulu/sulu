<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation;

use Imagine\Image\ImageInterface;

/**
 * Add the grayscale effect to an image.
 */
class GrayscaleTransformation implements TransformationInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface $image, $parameters)
    {
        $image->effects()->grayscale();

        return $image;
    }
}
