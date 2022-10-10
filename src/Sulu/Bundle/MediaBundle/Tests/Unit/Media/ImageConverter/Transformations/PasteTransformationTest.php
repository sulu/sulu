<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Imagick\Imagine as ImagickImagine;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\PasteTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Config\FileLocator;

/**
 * Class PasteTransformationTest
 * Test the paste transformation service.
 */
class PasteTransformationTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var PasteTransformation
     */
    protected $pasteTransformation;

    /**
     * @var ObjectProphecy<FileLocator>
     */
    protected $fileLocator;

    public function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocator::class);
        $this->fileLocator->locate('test.jpg')->willReturn(
            __DIR__ . '/../../../../Fixtures/files/photo.jpeg'
        );

        $imagine = $this->createImagine();

        $this->pasteTransformation = new PasteTransformation(
            $imagine,
            $this->fileLocator->reveal()
        );

        parent::setUp();
    }

    public function testPaste(): void
    {
        $image = $this->prophesize(ImageInterface::class);
        $image->getSize()->willReturn(new Box(700, 500));
        $image->paste(Argument::any(), Argument::any())->shouldBeCalled();

        $returnImage = $this->pasteTransformation->execute(
            $image->reveal(),
            [
                'image' => 'test.jpg',
            ]
        );

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }

    public function testNoPaste(): void
    {
        $image = $this->prophesize(ImageInterface::class);

        $this->expectException(\RuntimeException::class);

        $returnImage = $this->pasteTransformation->execute(
            $image->reveal(),
            []
        );
    }

    private function createImagine()
    {
        if (\class_exists(\Imagick::class)) {
            return new ImagickImagine();
        }

        return new GdImagine();
    }
}
