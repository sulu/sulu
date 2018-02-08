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
 * Adds the blur effect to an image.
 */
class BlurTransformation implements TransformationInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface $image, $parameters)
    {
        if (!isset($parameters['sigma'])) {
            throw new \RuntimeException('The parameter "sigma" is required for "blur" transformation.');
        }

        if (!is_numeric($parameters['sigma'])) {
            throw new \RuntimeException('The parameter "sigma" need to be a numeric value for "blur" transformation.');
        }

        $image->effects()->blur((float) $parameters['sigma']);

        return $image;
    }
}
