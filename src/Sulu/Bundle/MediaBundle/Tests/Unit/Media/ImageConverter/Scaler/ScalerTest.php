<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Scaler;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine as ImagickImagine;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\Scaler;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\ScalerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ScalerTest extends SuluTestCase
{
    /**
     * @var ScalerInterface
     */
    private $scaler;

    public function setUp(): void
    {
        parent::setUp();
        $this->scaler = new Scaler();
    }

    public function testScale(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200);

        $this->assertEquals(200, $image->getSize()->getWidth());
        $this->assertEquals(200, $image->getSize()->getHeight());
    }

    public function testScaleInset(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_INSET);

        $this->assertEquals(200, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());
    }

    public function testScaleForceRatio(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(100, 50);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_OUTBOUND, false);

        $this->assertEquals(100, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());

        $imagine = $this->createImagine();
        $imageBox = new Box(100, 50);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_OUTBOUND, true);

        $this->assertEquals(50, $image->getSize()->getWidth());
        $this->assertEquals(50, $image->getSize()->getHeight());
    }

    public function testScaleRetina(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(3000, 2000);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 200, 200, ImageInterface::THUMBNAIL_OUTBOUND, false, true);

        $this->assertEquals(400, $image->getSize()->getWidth());
        $this->assertEquals(400, $image->getSize()->getHeight());
    }

    public function testScaleWithFloatWidth(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(220, 442);
        $image = $imagine->create($imageBox);

        $image = $this->scaler->scale($image, 1273, null, ImageInterface::THUMBNAIL_OUTBOUND);

        $this->assertEquals(220, $image->getSize()->getWidth());
        $this->assertEquals(442, $image->getSize()->getHeight());
    }

    private function createImagine()
    {
        if (\class_exists(\Imagick::class)) {
            return new ImagickImagine();
        }

        return new GdImagine();
    }
}
