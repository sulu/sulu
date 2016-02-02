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
 * Class CropCommandTest
 * Test the crop command service.
 */
class CropCommandTest extends AbstractCommandTest
{
    protected $commandServiceName = 'sulu_media.image.command.crop';

    protected function getDataList()
    {
        return [
            [
                // Command Options
                'options' => [
                        'x' => 100,
                        'y' => 100,
                        'w' => 150,
                        'h' => 200,
                    ],
                // Tested Result
                'width' => 150,
                'height' => 200,
            ],
        ];
    }
}
