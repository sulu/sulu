<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

/**
 * Class CropTransformation.
 *
 * @deprecated
 */
class CropTransformation implements TransformationInterface
{
    public function execute(ImageInterface $image, $parameters)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '1.4',
            '%s is deprecated. Use the scale config instead',
            __CLASS__
        );
        $retina = isset($parameters['retina']) && 'false' != $parameters['retina'] ? 2 : 1;
        $x = isset($parameters['x']) ? \intval($parameters['x']) * $retina : 0;
        $y = isset($parameters['y']) ? \intval($parameters['y']) * $retina : 0;
        $width = isset($parameters['w']) ? \intval($parameters['w']) : 0;
        $height = isset($parameters['h']) ? \intval($parameters['h']) : 0;

        $point = new Point($x, $y);
        $box = new Box($width, $height);

        $image->crop($point, $box);

        return $image;
    }
}
