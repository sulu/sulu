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

use Imagine\Image\ImageInterface;

/**
 * Defines the operations of the ImageConverter
 * The ImageConverter is a interface to manage conversions of an Image.
 */
interface ImageConverterInterface
{
    /**
     * Convert an image and return the tmpPath.
     *
     * @param string $originalPath
     * @param array  $formatOptions
     *
     * @return ImageInterface
     */
    public function convert($originalPath, $formatOptions);
}
