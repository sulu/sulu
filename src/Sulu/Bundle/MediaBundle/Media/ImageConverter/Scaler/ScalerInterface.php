<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler;

use Imagine\Image\ImageInterface;

/**
 * Defines a scaling on an image.
 */
interface ScalerInterface
{
    /**
     * Scales an image according to the given parameters.
     *
     * @param ImageInterface $image The image to scale
     * @param int $x The value for the x dimension
     * @param int $y The value for the y dimension
     * @param string $mode The mode to use for scale, inset or outbound
     * @param bool $forceRatio Whether or not to force the format ratio when using the outbound mode
     * @param bool $retina Whether or not the scale is applied to a retina image
     *
     * @return ImageInterface The scaled image
     */
    public function scale(
        ImageInterface $image,
        $x,
        $y,
        $mode = ImageInterface::THUMBNAIL_OUTBOUND,
        $forceRatio = true,
        $retina = false
    );
}
