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
 * Defines the operations of the ImageConverter Transformations.
 * The TransformationInterface is a interface to manage image manipulation.
 */
interface TransformationInterface
{
    /**
     * Executes the transformation on a given image.
     *
     * @param ImageInterface $image
     * @param $parameters
     *
     * @return ImageInterface $image The modified image
     */
    public function execute(ImageInterface $image, $parameters);
}
