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
 * This exception is thrown when the username is not unique.
 */
class UsernameNotUniqueException extends SecurityException
{
    /**
     * The username which is not unique.
     *
     * @var string
     */
    private $username;

    public function __construct($username)
    {
        parent::__construct('a username has to be unique!', 1001);
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'username' => $this->username,
        ];
    }
}
