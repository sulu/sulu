<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Scaler;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\Scaler;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\ScalerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ScalerTest extends SuluTestCase
{
    /**
     * @var ScalerInterface
     */
    private $scaler;

    public function setUp()
    {
        parent::setUp();
        $this->scaler = new Scaler();
    }

    public function testScale()
    {
        $imagine = new Imagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200);

        $this->assertEquals(200, $image->getSize()->getWidth());
        $this->assertEquals(200, $image->getSize()->getHeight());
    }

    public function testScaleInset()
    {
        $imagine = new Imagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_INSET);

        $this->assertEquals(200, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());
    }

    public function testScaleForceRatio()
    {
        $imagine = new Imagine();
        $imageBox = new Box(100, 50);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_OUTBOUND, false);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());

        $imagine = new Imagine();
        $imageBox = new Box(100, 50);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_OUTBOUND, true);

        $this->assertEquals(50, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());
    }

    public function testScaleRetina()
    {
        $imagine = new Imagine();
        $imageBox = new Box(3000, 2000);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_OUTBOUND, false, true);

        $this->assertEquals(400, $image->getSize()->getWidth());
        $this->assertEquals(400, $image->getSize()->getHeight());
    }
}
