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

use Imagine\Image\ImageInterface;

/**
 * Defines the operations of the ImageConverter Commands
 * The ImageConverter Command is a interface to manage image manipulation.
 */
interface CommandInterface
{
    /**
     * @param ImageInterface $image
     * @param $parameters
     *
     * @return mixed
     */
    public function execute(ImageInterface &$image, $parameters);
}
