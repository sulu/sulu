<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

use Sulu\Bundle\SecurityBundle\Entity\User;

/**
 * This exception is thrown when a token-email for user without a token is requested.
 */
class NoTokenFoundException extends SecurityException
{
    /** @var User  */
    private $user;

    public function __construct(User $user)
    {
        parent::__construct(sprintf('The user "%s" has no token!', $user->getUsername()), 1006);
        $this->user = $user;
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'user' => $this->user->getUsername(),
        );
    }
}
