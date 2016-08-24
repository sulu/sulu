<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Cropping;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropping\Cropping;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropping\CroppingInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CroppingTest extends SuluTestCase
{
    /**
     * @var CroppingInterface
     */
    private $cropping;

    public function setUp()
    {
        parent::setUp();
        $this->cropping = new Cropping();
    }

    public function testCrop() {
        $imagine = new Imagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $image = $this->cropping->crop($image, 10, 10, 50, 100);

        $this->assertEquals(50, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());
    }
}
