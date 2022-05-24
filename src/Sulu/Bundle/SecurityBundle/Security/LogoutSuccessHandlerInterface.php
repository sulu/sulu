<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface as SymfonyLogoutSuccessHandlerInterface;

if (\interface_exists(SymfonyLogoutSuccessHandlerInterface::class)) {
    /**
     * @internal Just a internal bridge to be compatible with Symfony 5.4.
     */
    interface LogoutSuccessHandlerInterface extends SymfonyLogoutSuccessHandlerInterface
    {
    }
} else {
    /**
     * @internal Just a internal bridge to be compatible with Symfony 5.4.
     */
    interface LogoutSuccessHandlerInterface
    {
    }
}
