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
 * This Exception is thrown if a request with a not existing token tries to reset a password.
 */
class InvalidTokenException extends SecurityException
{
    /** @var string */
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
        parent::__construct(sprintf('The token "%s" does not exist!', $token), 1005);
    }

    public function getToken()
    {
        return $this->token;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'token' => $this->token,
        ];
    }
}
