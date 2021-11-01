<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

if (\interface_exists(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class)) {
    /**
     * @internal Backward compatibility Interface for Symfony Versions since 5.3.
     */
    interface PasswordAuthenticatedUserInterface extends \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface
    {
    }
} else {
    /**
     * @internal Backward compatibility Interface for Symfony Versions 4.4 - 5.2.
     */
    interface PasswordAuthenticatedUserInterface
    {
        public function getPassword(): ?string;
    }
}
