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

use Scheb\TwoFactorBundle\Model\BackupCodeInterface;

/*
 * Bridge interface to the scheb/2fa-backup-code TwoFactorInterface.
 */
if (\interface_exists(BackupCodeInterface::class)) {
    /**
     * @internal
     */
    trait BackupCodeTrait
    {
        public function isBackupCode(string $code): bool
        {
            return null !== $this->findBackupCode($code);
        }

        public function invalidateBackupCode(string $code): void
        {
            $twoFactor = $this->getTwoFactor();

            if (!$twoFactor) {
                return;
            }

            $key = $this->findBackupCode($code);

            if (null !== $key) {
                $options = $twoFactor->getOptions() ?? [];
                $backupCodes = $options['backupCodes'] ?? [];
                unset($backupCodes[$key]);
                $options['backupCodes'] = \array_values($backupCodes);
                $twoFactor->setOptions($options);
            }
        }

        public function addBackUpCode(string $backUpCode): void
        {
            $twoFactor = $this->getTwoFactor();

            if (!$twoFactor) {
                throw new \LogicException(
                    \sprintf(
                        'The method "%s::%s" should not be called without twoFactor being set.',
                        __CLASS__,
                        __METHOD__
                    )
                );
            }

            $options = $twoFactor->getOptions();
            $backupCodes = $options['backupCodes'] ?? [];

            if (!\in_array($backUpCode, $backupCodes)) {
                $options['backupCodes'][] = $backUpCode;
                $twoFactor->setOptions($options);
            }
        }

        /**
         * Returns the array key of the matching backup code or null if the code does not match.
         *
         * Backup codes are stored as password hashes. Plain text codes are also
         * supported for backwards compatibility with existing setups.
         */
        private function findBackupCode(string $code): ?int
        {
            $backupCodes = $this->getTwoFactor()?->getOptions()['backupCodes'] ?? [];

            foreach ($backupCodes as $key => $backupCode) {
                if (\hash_equals($backupCode, $code)) {
                    return $key;
                }
            }

            // hashed backup codes always have the length of BackupCodeGenerator::CODE_LENGTH, skip
            // the expensive hash verification for other inputs (e.g. totp or email codes), because
            // this method is called for every submitted two factor code
            if (\Sulu\Bundle\SecurityBundle\TwoFactor\BackupCodeGenerator::CODE_LENGTH !== \strlen($code)) {
                return null;
            }

            foreach ($backupCodes as $key => $backupCode) {
                if (\str_starts_with($backupCode, '$') && \password_verify($code, $backupCode)) {
                    return $key;
                }
            }

            return null;
        }
    }
} else {
    /**
     * @internal
     */
    trait BackupCodeTrait
    {
    }
}
