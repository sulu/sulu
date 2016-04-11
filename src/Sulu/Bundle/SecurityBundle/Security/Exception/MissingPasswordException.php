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

/**
 * This exception is thrown when the password is mandatory but missing.
 */
class MissingPasswordException extends SecurityException
{
    public function __construct()
    {
        parent::__construct('The password is missing!', 1002);
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
