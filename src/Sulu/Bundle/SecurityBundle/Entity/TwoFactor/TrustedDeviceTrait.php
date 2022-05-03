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
 * Bridge trait to the scheb/2fa-trusted-device TwoFactorInterface.
 */
if (\interface_exists(SchebTrustedDeviceInterface::class)) {
    /**
     * @internal
     */
    trait TrustedDeviceTrait
    {
        public function getTrustedTokenVersion(): int
        {
            // TODO return $this->trustedVersion;
        }
    }
} else {
    /**
     * @internal
     */
    trait TrustedDeviceTrait
    {
    }
}
