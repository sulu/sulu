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
 * Exception which is thrown when file is not found in media.
 */
class FileNotFoundException extends MediaException
{
    /**
     * @param int $id
     */
    public function __construct($id)
    {
        parent::__construct('File from the Media with ID "' . $id . '" not found', self::EXCEPTION_CODE_FILE_NOT_FOUND);
    }
}
