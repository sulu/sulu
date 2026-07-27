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
            return 'totp' === $this->getTwoFactor()?->getMethod();
        }

        public function getTotpAuthenticationUsername(): string
        {
            return $this->getUserIdentifier();
        }

        public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
        {
            $totpSecret = $this->getTwoFactor()?->getOptions()['totpSecret'] ?? '';

            // SHA1 with a 30 seconds period and 6 digits is the only configuration supported
            // by the common authenticator apps (Google Authenticator, Microsoft Authenticator, ...).
            return new TotpConfiguration($totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
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
