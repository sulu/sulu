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
 * Class ResizeCommandTest
 * Test the resize command service
 */
class ResizeCommandTest extends AbstractCommandTest
{
    protected $commandServiceName = 'sulu_media.image.command.resize';

    protected function getDataList()
    {
        return array(
            array(
                // Command Options
                'options' =>
                    array(
                        'x' => 200,
                        'y' => 100
                    ),
                'width' => 200,
                'height' => 100
            ),
            array(
                // Command Options
                'options' =>
                    array(
                        'x' => 5000,
                        'y' => 5000
                    ),
                // Tested Result
                'width' => 5000,
                'height' => 5000
            ),
        );
    }
}
