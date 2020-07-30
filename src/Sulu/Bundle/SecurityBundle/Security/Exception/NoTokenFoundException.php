<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

/**
 * This exception is thrown when the reset password route is requested without a token.
 */
class NoTokenFoundException extends SecurityException
{
    public function __construct()
    {
        parent::__construct('Token not found in query parameters!', 1006);
    }
}
