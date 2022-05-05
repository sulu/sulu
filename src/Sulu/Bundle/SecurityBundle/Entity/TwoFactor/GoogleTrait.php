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
            // TODO return $this->username;
        }

        public function getGoogleAuthenticatorSecret(): ?string
        {
            return $this->getTwoFactorOption('googleAuthenticatorSecret');
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
