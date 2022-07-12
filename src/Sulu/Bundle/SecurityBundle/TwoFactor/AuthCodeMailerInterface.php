<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\TwoFactor;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface as SchebAuthCodeMailerInterface;

if (\interface_exists(SchebAuthCodeMailerInterface::class)) {
    \class_alias(SchebAuthCodeMailerInterface::class, AuthCodeMailerInterface::class);
} else {
    /**
     * @internal
     */
    interface AuthCodeMailerInterface
    {
    }
}
