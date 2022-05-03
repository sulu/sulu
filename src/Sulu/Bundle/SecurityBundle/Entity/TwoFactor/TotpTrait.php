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

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

/*
 * Bridge trait to the scheb/2fa-totp TwoFactorInterface.
 */
if (\interface_exists(TwoFactorInterface::class)) {
    /**
     * @internal
     */
    trait TotpTrait
    {
        public function isTotpAuthenticationEnabled(): bool
        {
            return 'totp' === $this->twoFactorType;
        }

        public function getTotpAuthenticationUsername(): string
        {
            return $this->getUserIdentifier();
        }

        public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
        {
            // You could persist the other configuration options in the user entity to make it individual per user.
            return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 20, 8);
        }
    }
} else {
    /**
     * @internal
     */
    trait TotpTrait
    {
    }
}
