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

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

/*
 * Bridge trait to the scheb/2fa-email TwoFactorInterface.
 */
if (\interface_exists(TwoFactorInterface::class)) {
    /**
     * @internal
     */
    trait EmailTrait
    {
        public function isEmailAuthEnabled(): bool
        {
            return 'email' === $this->getTwoFactor()?->getMethod();
        }

        public function getEmailAuthRecipient(): string
        {
            return $this->getEmail();
        }

        public function getEmailAuthCode(): string
        {
            return $this->getTwoFactorOption('authCode');
        }

        public function setEmailAuthCode(string $authCode): void
        {
            $this->setTwoFactorOption('authCode', $authCode);
        }
    }
} else {
    /**
     * @internal
     */
    trait EmailTrait
    {
    }
}
