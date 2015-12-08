<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * Exception which is thrown when a file has invalid storage options.
 */
class InvalidStorageOptionsException extends MediaException
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message, self::EXCEPTION_INVALID_STORAGE_OPTIONS);
    }
}
