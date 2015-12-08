<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * Exception which is thrown when a file with this name exists.
 */
class FilenameWriteException extends MediaException
{
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct('A File "' . basename($path) . ' in "' . dirname($path) . '" errored when write file', self::EXCEPTION_FILE_WRITE_ERROR);
    }
}
