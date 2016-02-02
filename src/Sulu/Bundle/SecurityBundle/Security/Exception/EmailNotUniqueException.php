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
 * This Exception is thrown if the email for a user is not unique.
 */
class EmailNotUniqueException extends SecurityException
{
    private $email;

    public function __construct($email)
    {
        $this->email = $email;
        parent::__construct(sprintf('The email "%s" is not unique!', $email), 1004);
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'email' => $this->email,
        ];
    }
}
