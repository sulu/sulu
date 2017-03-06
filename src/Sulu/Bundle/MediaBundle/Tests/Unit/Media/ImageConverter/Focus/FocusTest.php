<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Focus;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus\Focus;

class FocusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Focus
     */
    private $focus;

    public function setUp()
    {
        $this->focus = new Focus();
    }

    /**
     * @dataProvider provideFocus
     */
    public function testFocus($imageWidth, $imageHeight, $x, $y, $width, $height, $cropX, $cropY, $cropWidth, $cropHeight)
    {
        $image = $this->prophesize(ImageInterface::class);
        $image->getSize()->willReturn(new Box($imageWidth, $imageHeight));

        $image->crop(new Point($cropX, $cropY), new Box($cropWidth, $cropHeight))->shouldBeCalled();

        $this->focus->focus($image->reveal(), $x, $y, $width, $height);
    }

    public function provideFocus()
    {
        return [
            [800, 800, 0, 0, 400, 400, 0, 0, 800, 800],
            [800, 800, 2, 2, 400, 400, 0, 0, 800, 800],
            [1200, 800, 0, 0, 400, 400, 0, 0, 800, 800],
            [1200, 800, 1, 1, 400, 400, 200, 0, 800, 800],
            [1200, 800, 2, 1, 400, 400, 400, 0, 800, 800],
            [800, 1200, 0, 0, 400, 400, 0, 0, 800, 800],
            [800, 1200, 0, 1, 400, 400, 0, 200, 800, 800],
            [800, 1200, 0, 2, 400, 400, 0, 400, 800, 800],
            [1200, 800, null, null, 400, 400, 200, 0, 800, 800],
            [300, 220, null, null, 300, 220, 0, 0, 300, 220],
        ];
    }
}
