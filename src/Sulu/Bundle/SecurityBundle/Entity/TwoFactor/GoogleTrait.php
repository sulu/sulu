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

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

/*
 * Bridge trait to the scheb/2fa-google-authenticator TwoFactorInterface.
 */
if (\interface_exists(TwoFactorInterface::class)) {
    /**
     * @internal
     */
    trait GoogleTrait
    {
        public function isGoogleAuthenticatorEnabled(): bool
        {
            return 'google' === $this->getTwoFactor()?->getMethod();
        }

        public function getGoogleAuthenticatorUsername(): string
        {
            $options = $this->getTwoFactor()?->getOptions() ?? [];

            return ($options['googleAuthenticatorUsername'] ?? null) ?: $this->getUserIdentifier();
        }

        public function getGoogleAuthenticatorSecret(): ?string
        {
            $options = $this->getTwoFactor()?->getOptions() ?? [];

            // the pending secret is used to generate the QR code and to verify the code
            // during the setup, before the method gets activated
            return $options['googleAuthenticatorSecret'] ?? $options['pendingGoogleAuthenticatorSecret'] ?? null;
        }

        public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
        {
            $this->setTwoFactorOption('googleAuthenticatorSecret', $googleAuthenticatorSecret);
        }
    }
} else {
    /**
     * @internal
     */
    trait GoogleTrait
    {
    }
}
