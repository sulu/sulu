<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\PasteTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Config\FileLocator;

/**
 * Class PasteTransformationTest
 * Test the paste transformation service.
 */
class PasteTransformationTest extends SuluTestCase
{
    /**
     * @var PasteTransformation
     */
    protected $pasteTransformation;

    /**
     * @var FileLocator
     */
    protected $fileLocator;

    public function setUp()
    {
        $this->fileLocator = $this->prophesize(FileLocator::class);
        $this->fileLocator->locate('test.jpg')->willReturn(
            __DIR__ . '/../../../../app/Resources/images/photo.jpeg'
        );

        $this->pasteTransformation = new pasteTransformation(
            $this->fileLocator->reveal()
        );

        parent::setUp();
    }

    public function testPaste()
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

    public function testNoPaste()
    {
        $image = $this->prophesize(ImageInterface::class);
        $image->getSize()->willReturn(new Box(700, 500));
        $image->paste(Argument::any(), Argument::any())->shouldNotBeCalled();

        $returnImage = $this->pasteTransformation->execute(
            $image->reveal(),
            []
        );

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }
}
