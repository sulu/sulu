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
 * This exception is thrown when a token-email for user without a token is requested.
 */
class NoTokenFoundException extends SecurityException
{
    /** @var UserInterface */
    private $user;

    public function __construct(UserInterface $user)
    {
        parent::__construct(sprintf('The user "%s" has no token!', $user->getUsername()), 1006);
        $this->user = $user;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'user' => $this->user->getUsername(),
        ];
    }
}
