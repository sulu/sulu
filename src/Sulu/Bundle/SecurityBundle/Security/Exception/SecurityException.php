<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

use Exception;

/**
 * This exception is a general security exception.
 * Exceptions related with the security bundle should inherit form this exception and use it's exception codes.
 */
class SecurityException extends Exception
{
    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
