<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Command;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

class ScaleCommand implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface &$image, $parameters)
    {
        $parameters = array_merge([
            'retina' => false,
            'forceRatio' => true,
            'x' => null,
            'y' => null,
            'mode' => $image::THUMBNAIL_OUTBOUND,
        ], $parameters);

        list($newWidth, $newHeight) = $this->getHeightWidth($parameters, $image->getSize());

        $image = $image->thumbnail(new Box($newWidth, $newHeight), $parameters['mode']);
    }

    /**
     * @param $parameters
     * @param \Imagine\Image\BoxInterface $size
     *
     * @return array
     */
    protected function getHeightWidth($parameters, $size)
    {
        $newWidth = $parameters['x'];
        $newHeight = $parameters['y'];

        // retina x2
        if ($parameters['retina']) {
            $newWidth = $parameters['x'] * 2;
            $newHeight = $parameters['y'] * 2;
        }

        // calculate height when not set
        if (!$newHeight) {
            $newHeight = $size->getHeight() / $size->getWidth() * $newWidth;
        }

        // calculate width when not set
        if (!$newWidth) {
            $newWidth = $size->getWidth() / $size->getHeight() * $newHeight;
        }

        // if image is smaller keep ratio
        // e.g. when a square image is requested (200x200) and the original image is smaller (150x100)
        //      it still returns a squared image (100x100)
        if ($parameters['forceRatio']) {
            if ($newWidth > $size->getWidth()) {
                list($newHeight, $newWidth) = $this->getSizeInSameRatio(
                    $newHeight,
                    $newWidth,
                    $size->getWidth()
                );
            }

            if ($newHeight > $size->getHeight()) {
                list($newWidth, $newHeight) = $this->getSizeInSameRatio(
                    $newWidth,
                    $newHeight,
                    $size->getHeight()
                );
            }
        }

        return [$newWidth, $newHeight];
    }

    /**
     * @param $size1
     * @param $size2
     * @param $originalSize
     *
     * @return array
     */
    protected function getSizeInSameRatio($size1, $size2, $originalSize)
    {
        if ($size1) {
            $size1 = $size1 / $size2 * $originalSize;
        }

        $size2 = $originalSize;

        return [$size1, $size2];
    }
}
