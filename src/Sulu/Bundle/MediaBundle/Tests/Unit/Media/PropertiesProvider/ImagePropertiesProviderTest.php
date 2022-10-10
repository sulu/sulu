<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\PropertiesProvider;

use Contao\ImagineSvg\Imagine as SvgImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\ImagePropertiesProvider;
use Sulu\Bundle\MediaBundle\Tests\Functional\Traits\CreateUploadedFileTrait;

class ImagePropertiesProviderTest extends TestCase
{
    use ProphecyTrait;
    use CreateUploadedFileTrait;

    /**
     * @var ObjectProphecy<ImagineInterface>
     */
    private $imagine;

    /**
     * @var ImagePropertiesProvider
     */
    private $imagePropertiesProvider;

    protected function setUp(): void
    {
        $this->imagine = $this->prophesize(ImagineInterface::class);

        $this->imagePropertiesProvider = new ImagePropertiesProvider(
            $this->imagine->reveal(),
            new SvgImagine()
        );
    }

    public function testProvideVideo(): void
    {
        // prepare data
        $uploadedFile = $this->createUploadedFileVideo();

        // test function
        $this->assertSame(
            [],
            $this->imagePropertiesProvider->provide($uploadedFile)
        );
    }

    public function testProvideImage(): void
    {
        // prepare data
        $uploadedFile = $this->createUploadedFileImage();

        // prepare expected service calls
        $image = $this->prophesize(ImageInterface::class);
        $this->imagine->open(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($image->reveal());

        $size = new Box(360, 240);
        $image->getSize()
            ->shouldBeCalledOnce()
            ->willReturn($size);

        // test function
        $this->assertSame(
            [
                'width' => 360,
                'height' => 240,
            ],
            $this->imagePropertiesProvider->provide($uploadedFile)
        );
    }

    public function testProvideSvgImage(): void
    {
        // prepare data
        $uploadedFile = $this->createUploadedFileSvgImage(400, 200);

        // test function
        $this->assertSame(
            [
                'width' => 400,
                'height' => 200,
            ],
            $this->imagePropertiesProvider->provide($uploadedFile)
        );
    }
}
