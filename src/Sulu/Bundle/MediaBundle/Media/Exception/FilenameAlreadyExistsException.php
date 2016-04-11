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

class FilenameAlreadyExistsException extends MediaException
{
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct('A File with the name "' . basename($path) . ' exists in "' . dirname($path) . '"', self::EXCEPTION_FILENAME_ALREADY_EXISTS);
    }
}
