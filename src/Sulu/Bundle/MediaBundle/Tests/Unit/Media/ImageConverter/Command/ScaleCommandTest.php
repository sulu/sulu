<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Command;

/**
 * Class ScaleCommandTest
 * Test the scale command service
 */
class ScaleCommandTest extends AbstractCommandTest
{
    protected $commandServiceName = 'sulu_media.image.command.scale';

    protected function getDataList()
    {
        return array(
            array(
                // Command Options
                'options' =>
                    array(
                        'x' => 200,
                        'y' => 100,
                        'forceRatio' => false,
                    ),
                // Tested Result
                'width' => 200,
                'height' => 100
            ),
            array(
                // Command Options
                'options' =>
                    array(
                        'x' => 5000,
                        'y' => 5000,
                        'forceRatio' => true,
                    ),
                // Tested Result
                'width' => 500,
                'height' => 500
            ),
            array(
                // Command Options
                'options' =>
                    array(
                        'x' => 5000,
                        'y' => 5000,
                        'forceRatio' => false,
                    ),
                // Tested Result
                'width' => 700,
                'height' => 500
            ),
        );
    }
}
