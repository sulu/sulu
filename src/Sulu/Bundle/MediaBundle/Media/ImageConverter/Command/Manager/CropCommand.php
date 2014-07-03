<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Command;

use Imagine\Image\Box;
use Imagine\Image\Point;

class CropCommand implements CommandInterface {

    /**
     * {@inheritdoc}
     */
    public function execute(&$image, $parameters)
    {
        $x = isset($parameters['x']) ? intval($parameters['x']) : 0;
        $y = isset($parameters['y']) ? intval($parameters['y']) : 0;
        $width = isset($parameters['w']) ? intval($parameters['w']) : 0;
        $height = isset($parameters['h']) ? intval($parameters['h']) : 0;

        $point = new Point($x, $y);
        $box = new Box($width, $height);

        $image->crop($point, $box);
    }

} 
