<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Exception;

use Sulu\Component\Security\Authentication\RoleInterface;

class AssignAnonymousRoleException extends \LogicException
{
    public function __construct(RoleInterface $role, $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf(
                'It is not allowed to add an anonymous role to a user. Tried to add role "%s".',
                $role->getName()
            ),
            $code,
            $previous
        );
    }
}
