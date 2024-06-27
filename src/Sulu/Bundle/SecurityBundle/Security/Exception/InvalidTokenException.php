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
 * This Exception is thrown if a request with a not existing token tries to reset a password.
 */
class InvalidTokenException extends SecurityException
{
    /**
     * @param string $token
     */
    public function __construct(private $token, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('The token "%s" does not exist!', $this->token), 1005, $previous);
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
