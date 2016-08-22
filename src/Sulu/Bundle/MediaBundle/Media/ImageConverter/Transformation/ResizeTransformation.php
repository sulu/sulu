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

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

/**
 * Class ResizeTransformation.
 *
 * @deprecated
 */
class ResizeTransformation implements TransformationInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface $image, $parameters)
    {
        @trigger_error(
            'ScaleTransformation is deprecated since version 1.4. Use the scale config instead',
            E_USER_DEPRECATED
        );
        $size = $image->getSize();

        $retina = isset($parameters['retina']) && $parameters['retina'] != 'false' ? 2 : 1;

        $newWidth = isset($parameters['x']) ? intval($parameters['x']) * $retina : null;
        $newHeight = isset($parameters['y']) ? intval($parameters['y']) * $retina : null;

        if ($newHeight == null) {
            $newHeight = $size->getHeight() / $size->getWidth() * $newWidth;
        }
        if ($newWidth == null) {
            $newWidth = $size->getWidth() / $size->getHeight() * $newHeight;
        }
        $image->resize(new Box($newWidth, $newHeight));

        return $image;
    }
}
