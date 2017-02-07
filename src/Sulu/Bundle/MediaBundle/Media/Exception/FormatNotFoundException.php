<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * Exception which is thrown when image format could not be found.
 */
class FormatNotFoundException extends MediaException
{
    /**
     * @param string $formatKey
     */
    public function __construct($formatKey)
    {
        parent::__construct('Format with key ' . $formatKey . ' not found', self::EXCEPTION_FORMAT_NOT_FOUND);
    }
}
