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
 * Class ScaleTransformationDeprecated.
 *
 * @deprecated
 */
class ScaleTransformationDeprecated implements TransformationInterface
{
    private $scaleTransformation;

    /**
     * Constructs the transformation with the non-deprecated version.
     * This implementation just calls the non-deprecated version.
     *
     * @param TransformationInterface $scaleTransformation
     */
    public function __construct(TransformationInterface $scaleTransformation)
    {
        $this->scaleTransformation = $scaleTransformation;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface $image, $parameters)
    {
        @trigger_error(
            'ScaleTransformation is deprecated since version 1.4. Use the scale config instead',
            E_USER_DEPRECATED
        );

        return $this->scaleTransformation->execute($image, $parameters);
    }
}
