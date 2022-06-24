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

use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface as SchebTrustedDeviceInterface;

/*
 * Bridge interface to the scheb/2fa-trusted-device TwoFactorInterface.
 */
if (\interface_exists(SchebTrustedDeviceInterface::class)) {
    /**
     * @internal
     */
    interface TrustedDeviceInterface extends SchebTrustedDeviceInterface
    {
    }
} else {
    /**
     * @internal
     */
    interface TrustedDeviceInterface
    {
    }
}
