<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter;

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper\CropperInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus\FocusInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImagineImageConverter;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\MediaImageExtractorInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\ScalerInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\TransformationPoolInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class ImagineImageConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var MediaImageExtractorInterface
     */
    private $mediaImageExtractor;

    /**
     * @var TransformationPoolInterface
     */
    private $transformationPool;

    /**
     * @var FocusInterface
     */
    private $focus;

    /**
     * @var ScalerInterface
     */
    private $scaler;

    /**
     * @var CropperInterface
     */
    private $cropper;

    /**
     * @var ImageConverterInterface
     */
    private $imagineImageConverter;

    public function setUp()
    {
        $this->imagine = $this->prophesize(ImagineInterface::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->mediaImageExtractor = $this->prophesize(MediaImageExtractorInterface::class);
        $this->transformationPool = $this->prophesize(TransformationPoolInterface::class);
        $this->focus = $this->prophesize(FocusInterface::class);
        $this->scaler = $this->prophesize(ScalerInterface::class);
        $this->cropper = $this->prophesize(CropperInterface::class);

        $this->imagineImageConverter = new ImagineImageConverter(
            $this->imagine->reveal(),
            $this->storage->reveal(),
            $this->mediaImageExtractor->reveal(),
            $this->transformationPool->reveal(),
            $this->focus->reveal(),
            $this->scaler->reveal(),
            $this->cropper->reveal(),
            [
                '640x480' => [
                    'options' => [],
                ],
            ]
        );
    }

    public function testConvertIsBinaryString()
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions('{}');

        $this->storage->loadAsString('test.jpg', 1, '{}')->willReturn('image-content');
        $this->mediaImageExtractor->extract('image-content')->willReturn('image-content');
        $this->imagine->load('image-content')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-content');

        $this->focus->focus(Argument::any())->shouldNotBeCalled();

        $this->assertEquals('new-image-content', $this->imagineImageConverter->convert($fileVersion, '640x480'));
    }

    public function testConvertNoFocusOnInset()
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions('{}');

        $this->storage->loadAsString('test.jpg', 1, '{}')->willReturn('image-content');
        $this->mediaImageExtractor->extract('image-content')->willReturn('image-content');
        $this->imagine->load('image-content')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-content');

        $this->focus->focus(Argument::any())->shouldNotBeCalled();

        $this->assertEquals('new-image-content', $this->imagineImageConverter->convert($fileVersion, '640x480'));
    }

    public function testConvertWithImageExtension()
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.svg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions('{}');

        $this->storage->loadAsString('test.svg', 1, '{}')->willReturn('image-content');
        $this->mediaImageExtractor->extract('image-content')->willReturn('image-content');
        $this->imagine->load('image-content')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('png', [])->willReturn('new-image-content');

        $this->assertEquals('new-image-content', $this->imagineImageConverter->convert($fileVersion, '640x480'));
    }

    public function testConvertCmykToRgb()
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);
        $palette->name()->willReturn('cmyk');

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions('{}');

        $this->storage->loadAsString('test.jpg', 1, '{}')->willReturn('image-content');
        $this->mediaImageExtractor->extract('image-content')->willReturn('image-content');
        $this->imagine->load('image-content')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->usePalette(Argument::type(RGB::class))->shouldBeCalled();
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-content');

        $this->assertEquals('new-image-content', $this->imagineImageConverter->convert($fileVersion, '640x480'));
    }

    public function testConvertNotExistingMedia()
    {
        $this->setExpectedException(ImageProxyMediaNotFoundException::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions('{}');

        $this->storage->loadAsString('test.jpg', 1, '{}')->willThrow(ImageProxyMediaNotFoundException::class);

        $this->imagineImageConverter->convert($fileVersion, '640x480');
    }
}
