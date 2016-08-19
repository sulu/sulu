<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

use Imagine\Image\ImageInterface;

/**
 * Class ScaleTransformationTest
 * Test the scale transformation service.
 */
class ScaleTransformationTest extends AbstractTransformationTest
{
    protected $transformationServiceName = 'sulu_media.image.transformation.scale';

    protected function getDataList()
    {
        return [
            [
                // Transformation Options
                'options' => [
                        'x' => 200,
                        'y' => 100,
                        'forceRatio' => false,
                    ],
                // Tested Result
                'width' => 200,
                'height' => 100,
            ],
            [
                // Transformation Options
                'options' => [
                        'x' => 200,
                        'y' => 100,
                        'forceRatio' => false,
                        'retina' => true,
                    ],
                // Tested Result
                'width' => 400,
                'height' => 200,
            ],
            [
                // Transformation Options
                'options' => [
                        'x' => 5000,
                        'y' => 5000,
                        'forceRatio' => true,
                    ],
                // Tested Result
                'width' => 500,
                'height' => 500,
            ],
            [
                // Transformation Options
                'options' => [
                    'x' => 700,
                    'y' => 250,
                    'forceRatio' => true,
                ],
                // Image width
                'imageWidth' => 692,
                'imageHeight' => 230,
                // Tested Result
                'width' => 644,
                'height' => 230,
            ],
            [
                // Transformation Options
                'options' => [
                    'x' => 250,
                    'y' => 700,
                    'forceRatio' => true,
                ],
                // Image width
                'imageWidth' => 692,
                'imageHeight' => 230,
                // Tested Result
                'width' => 82,
                'height' => 230,
            ],
            [
                // Transformation Options
                'options' => [
                    'x' => 5000,
                    'y' => 5000,
                    'forceRatio' => false,
                ],
                // Tested Result
                'width' => 700,
                'height' => 500,
            ],
            [
                // Transformation Options
                'options' => [
                    'mode' => ImageInterface::THUMBNAIL_INSET,
                    'x' => 200,
                    'y' => 200,
                ],
                // Tested Result
                'width' => 200,
                'height' => 4,
                // Source image
                'imageHeight' => 6,
                'imageWidth' => 300,
            ],
            [
                // Transformation Options
                'options' => [
                    'mode' => ImageInterface::THUMBNAIL_INSET,
                    'x' => 600,
                    'y' => 600,
                ],
                // Tested Result
                'width' => 600,
                'height' => 429,
            ],
            [
                // Transformation Options
                'options' => [
                    'mode' => ImageInterface::THUMBNAIL_INSET,
                    'x' => 1000,
                    'y' => 1000,
                ],
                // Tested Result
                'width' => 700,
                'height' => 500,
            ],
            [
                // Transformation Options
                'options' => [
                    'mode' => ImageInterface::THUMBNAIL_INSET,
                    'x' => 300,
                    'y' => 300,
                ],
                // Tested Result
                'width' => 300,
                'height' => 214,
            ],
        ];
    }
}
