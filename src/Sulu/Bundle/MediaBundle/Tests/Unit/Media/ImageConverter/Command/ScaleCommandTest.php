<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Command;

/**
 * Class ScaleCommandTest
 * Test the scale command service.
 */
class ScaleCommandTest extends AbstractCommandTest
{
    protected $commandServiceName = 'sulu_media.image.command.scale';

    protected function getDataList()
    {
        return [
            [
                // Command Options
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
                // Command Options
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
                // Command Options
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
                // Command Options
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
                // Command Options
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
                // Command Options
                'options' => [
                    'x' => 5000,
                    'y' => 5000,
                    'forceRatio' => false,
                ],
                // Tested Result
                'width' => 700,
                'height' => 500,
            ],
        ];
    }
}
