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
interface MediaImageExtractorInterface
{
    /**
     * Extracts an image out of the given content.
     *
     * @param string $content
     *
     * @return string
     */
    public function extract($content);
}
