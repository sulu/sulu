<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Cropper;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Imagick\Imagine as ImagickImagine;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper\Cropper;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper\CropperInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CropperTest extends SuluTestCase
{
    /**
     * @var CropperInterface
     */
    private $cropper;

    public function setUp(): void
    {
        parent::setUp();
        $this->cropper = new Cropper();
    }

    public function testCrop(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $image = $this->cropper->crop($image, 10, 10, 50, 100);

        $this->assertEquals(50, $image->getSize()->getWidth());
        $this->assertEquals(100, $image->getSize()->getHeight());
    }

    public function testValid(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
                'y' => 200,
            ],
        ];
        $valid = $this->cropper->isValid($image, 10, 20, 600, 400, $format);

        $this->assertTrue($valid);
    }

    public function testValidWithOneDimension(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
            ],
        ];

        $valid = $this->cropper->isValid($image, 10, 0, 600, 500, $format);
        $this->assertTrue($valid);

        $valid = $this->cropper->isValid($image, 10, 20, 600, 20, $format);
        $this->assertTrue($valid);
    }

    public function testValidSameSize(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
                'y' => 200,
            ],
        ];
        $valid = $this->cropper->isValid($image, 10, 20, 300, 200, $format);

        $this->assertTrue($valid);
    }

    public function testValidTooSmallButMaxWidthForImage(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(60, 100);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
                'y' => 200,
            ],
        ];
        $valid = $this->cropper->isValid($image, 0, 0, 60, 40, $format);

        $this->assertTrue($valid);
    }

    public function testNotValidTooSmall(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
                'y' => 200,
            ],
        ];
        $valid = $this->cropper->isValid($image, 10, 20, 150, 100, $format);

        $this->assertFalse($valid);
    }

    public function testNotValidExceedX(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
                'y' => 200,
            ],
        ];
        $valid = $this->cropper->isValid($image, 500, 20, 600, 400, $format);

        $this->assertFalse($valid);
    }

    public function testNotValidExceedY(): void
    {
        $imagine = $this->createImagine();
        $imageBox = new Box(1000, 500);
        $image = $imagine->create($imageBox);

        $format = [
            'scale' => [
                'x' => 300,
                'y' => 200,
            ],
        ];
        $valid = $this->cropper->isValid($image, 10, 200, 600, 400, $format);

        $this->assertFalse($valid);
    }

    private function createImagine()
    {
        if (\class_exists(\Imagick::class)) {
            return new ImagickImagine();
        }

        return new GdImagine();
    }
}
