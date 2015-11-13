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
 * Exception which is thrown when a system file is not found.
 */
class SystemFileNotFoundException extends MediaException
{
    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        parent::__construct(
            sprintf('File not found in "%s".' , $uri),
            self::EXCEPTION_CODE_SYSTEM_FILE_NOT_FOUND
        );
    }
}
