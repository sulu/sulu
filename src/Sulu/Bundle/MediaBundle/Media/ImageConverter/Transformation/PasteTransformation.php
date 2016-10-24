<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Imagick\Imagine as ImagickImagine;
use Symfony\Component\Config\FileLocator;

/**
 * Class PasteTransformation.
 */
class PasteTransformation implements TransformationInterface
{
    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * MaskTransformation constructor.
     *
     * @param FileLocator $fileLocator
     */
    public function __construct(
        FileLocator $fileLocator
    ) {
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface $image, $parameters)
    {
        $maskPath = isset($parameters['image']) ? $this->fileLocator->locate($parameters['image']) : null;

        if (!$maskPath) {
            return $image;
        }

        $originalWidth = $image->getSize()->getWidth();
        $originalHeight = $image->getSize()->getHeight();
        $top = isset($parameters['top']) ? $parameters['top'] : 0;
        $left = isset($parameters['left']) ? $parameters['left'] : 0;

        $width = isset($parameters['width']) ? $parameters['width'] : $originalWidth;
        $height = isset($parameters['height']) ? $parameters['height'] : $originalHeight;

        // imagine will error when mask is bigger then the given image
        // this could happen in forceRatio true mode so we need also scale the mask
        if ($width > $originalWidth) {
            $width = $originalWidth;
            $height = (int) ($height / $width * $originalWidth);
        }

        if ($height > $originalHeight) {
            $height = $originalHeight;
            $width = (int) ($width / $height * $originalHeight);
        }

        // create mask
        $mask = $this->createMask(
            $maskPath,
            $width,
            $height
        );

        // add mask to image
        $image->paste($mask, new Point($top, $left));

        return $image;
    }

    /**
     * Create mask.
     *
     * @param $maskPath
     * @param $width
     * @param $height
     *
     * @return ImageInterface
     */
    protected function createMask($maskPath, $width, $height)
    {
        try {
            // todo get this from a service
            $imagine = new ImagickImagine();
        } catch (\RuntimeException $ex) {
            $imagine = new GdImagine();
        }

        $mask = $imagine->open($maskPath);
        $mask->resize(
            new Box(
                $width ?: 1,
                $height ?: 1
            )
        );

        return $mask;
    }
}
