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

class ResizeCommand implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(ImageInterface &$image, $parameters)
    {
        $size = $image->getSize();

        $retina = isset($parameters['retina']) && $parameters['retina'] != 'false' ? 2 : 1;

        $newWidth = isset($parameters['x']) ? intval($parameters['x']) * $retina : null;
        $newHeight = isset($parameters['y']) ? intval($parameters['y']) * $retina : null;

        if ($newHeight == null) {
            $newHeight = $size->getHeight() / $size->getWidth() * $newWidth;
        }
        if ($newWidth == null) {
            $newWidth = $size->getWidth() / $size->getHeight() * $newHeight;
        }
        $image->resize(new Box($newWidth, $newHeight));
    }
}
