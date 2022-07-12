<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity\TwoFactor;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

/*
 * Bridge interface to the scheb/2fa-totp TwoFactorInterface.
 */
if (\interface_exists(TwoFactorInterface::class)) {
    /*
     * @internal
     */
    \class_alias(TwoFactorInterface::class, TotpInterface::class);
} else {
    /**
     * @internal
     */
    interface TotpInterface
    {
    }
}
