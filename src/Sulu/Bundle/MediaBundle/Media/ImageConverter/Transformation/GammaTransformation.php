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
 * Adds the gamma effect to an image.
 */
class GammaTransformation implements TransformationInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface $image, $parameters)
    {
        if (!isset($parameters['correction'])) {
            throw new \RuntimeException('The parameter "correction" is required for "gamma" transformation.');
        }

        if (!is_numeric($parameters['correction'])) {
            throw new \RuntimeException(
                'The parameter "correction" need to be a numeric value for "gamma" transformation.'
            );
        }

        $image->effects()->gamma((float) $parameters['correction']);

        return $image;
    }
}
