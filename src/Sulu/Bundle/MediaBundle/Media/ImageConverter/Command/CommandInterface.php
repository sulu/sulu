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

use Imagine\Image\ImageInterface;

interface CommandInterface {

    /**
     * @param ImageInterface $image
     * @param $parameters
     * @return mixed
     */
    public function execute(&$image, $parameters);

} 
