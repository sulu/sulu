<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Imagick\Imagine;
use RuntimeException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidFormatOptionsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileTypeException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\Manager\ManagerInterface;

/**
 * Sulu imagine converter for media.
 */
class ImagineImageConverter implements ImageConverterInterface
{
    /**
     * @var ManagerInterface
     */
    private $transformationManager;

    /**
     * @param ManagerInterface $transformationManager
     */
    public function __construct(ManagerInterface $transformationManager)
    {
        $this->transformationManager = $transformationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($originalPath, array $format)
    {
        $imagine = $this->newImage();
        $image = null;

        try {
            $image = $imagine->open($originalPath);
        } catch (\RuntimeException $e) {
            if (file_exists($originalPath)) {
                throw new InvalidFileTypeException($e->getMessage());
            }
            throw new ImageProxyMediaNotFoundException($e->getMessage());
        }

        $image = $this->toRGB($image);

        if (isset($format['transformations'])) {
            $image = $this->applyTransformations($image, $format['transformations']);
        }

        return $image;
    }

    /**
     * Creates a new image
     *
     * @return ImageInterface
     */
    private function newImage()
    {
        try {
            return new ImagickImagine();
        } catch (RuntimeException $ex) {
            return new GdImagine();
        }
    }

    /**
     * Applies an array of transformations on a passed image
     *
     * @param ImageInterface $image
     * @param $tansformations
     * @throws ImageProxyInvalidFormatOptionsException
     *
     * @return ImageInterface $image The modified image
     */
    private function applyTransformations(ImageInterface $image, $tansformations)
    {
        foreach ($tansformations as $transformation) {
            if (!isset($transformation['effect'])) {
                throw new ImageProxyInvalidFormatOptionsException('Effect not found');
            }
            $image = $this->call($image, $transformation['effect'], $transformation['parameters']);
        }

        return $image;
    }

    /**
     * Ensures that the color mode of the passed image is RGB
     *
     * @param ImageInterface $image
     *
     * @return ImageInterface $image The modified image
     */
    private function toRGB(ImageInterface $image)
    {
        if ($image->palette()->name() == 'cmyk') {
            $image->usePalette(new RGB());
        }

        return $image;
    }

    /**
     * Calls a given transformation with given parameters on the passed image
     *
     * @param ImageInterface $image
     * @param $transformation
     * @param $parameters
     *
     * @return ImageInterface $image The modified image
     */
    private function call(ImageInterface $image, $transformation, $parameters)
    {
        if (count($image->layers())) {
            $countLayer = 0;
            $image->layers()->coalesce();

            /** @var ImageInterface $temporarImage */
            $temporarImage = null;
            foreach ($image->layers() as $layer) {
                $countLayer += 1;
                $this->transformationManager->get($transformation)->execute($layer, $parameters);
                if ($countLayer == 1) {
                    $temporarImage = $layer; // use first layer as main image
                } else {
                    $temporarImage->layers()->add($layer);
                }
            }
            $image = $temporarImage;
        } else {
            $this->transformationManager->get($transformation)->execute($image, $parameters);
        }

        return $image;
    }
}
