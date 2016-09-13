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

class FileVersionNotFoundException extends MediaException
{
    /**
     * @param int $id
     * @param int $version
     */
    public function __construct($id, $version)
    {
        parent::__construct('File version ' . $version . ' of the Media with id ' . $id . ' ', self::EXCEPTION_CODE_FILE_VERSION_NOT_FOUND);
    }
}
