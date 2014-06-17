<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Imagine\Image\ImageInterface;

interface ImageConverterInterface {

    /**
     * Convert an image and return the tmpPath
     * @param $originalPath
     * @param $format
     * @return ImageInterface
     */
    public function convert($originalPath, $format);

    /**
     * Get all formats
     * @return array
     */
    public function getFormats();

} 
