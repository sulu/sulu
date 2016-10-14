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

/**
 * Defines the interface for loading the binary data of an image representing a file.
 */
interface ImageLoaderInterface
{
    /**
     * Loads the file at the given path and returns the binary data of an image representing that file.
     *
     * @param string $path
     *
     * @return string
     */
    public function load($path);
}
