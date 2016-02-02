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

use Sulu\Component\Security\Authentication\UserInterface;

/**
 * This exception is thrown if a user requests to much resetting-emails.
 */
class TokenEmailsLimitReachedException extends SecurityException
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var UserInterface
     */
    private $user;

    public function __construct($limit, UserInterface $user)
    {
        parent::__construct('The resetting-email limit has been reached!', 1007);
        $this->limit = $limit;
        $this->user = $user;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'limit' => $this->limit,
            'user' => $this->user->getUsername(),
        ];
    }
}
