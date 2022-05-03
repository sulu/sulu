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
        /**
         * Check if it is a valid backup code.
         */
        public function isBackupCode(string $code): bool
        {
            // TODO
            return \in_array($code, $this->backupCodes);
        }

        /**
         * Invalidate a backup code.
         */
        public function invalidateBackupCode(string $code): void
        {
            // TODO
            $key = \array_search($code, $this->backupCodes);
            if (false !== $key) {
                unset($this->backupCodes[$key]);
            }
        }

        /**
         * Add a backup code.
         */
        public function addBackUpCode(string $backUpCode): void
        {
            // TODO
            if (!\in_array($backUpCode, $this->backupCodes)) {
                $this->backupCodes[] = $backUpCode;
            }
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
