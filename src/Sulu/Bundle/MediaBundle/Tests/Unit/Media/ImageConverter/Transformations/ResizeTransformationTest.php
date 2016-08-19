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

/**
 * Class ResizeTransformationTest
 * Test the resize transformation service.
 */
class ResizeTransformationTest extends AbstractTransformationTest
{
    protected $transformationServiceName = 'sulu_media.image.transformation.resize';

    protected function getDataList()
    {
        return [
            [
                // Transformation Options
                'options' => [
                        'x' => 200,
                        'y' => 100,
                    ],
                'width' => 200,
                'height' => 100,
            ],
            [
                // Transformation Options
                'options' => [
                        'x' => 5000,
                        'y' => 5000,
                    ],
                // Tested Result
                'width' => 5000,
                'height' => 5000,
            ],
        ];
    }
}
