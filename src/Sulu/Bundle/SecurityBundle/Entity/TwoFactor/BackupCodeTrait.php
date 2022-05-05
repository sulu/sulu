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
            $backupCodes = $this->getTwoFactor()?->getOptions()['backupCodes'] ?? [];

            return \in_array($code, $backupCodes);
        }

        public function invalidateBackupCode(string $code): void
        {
            $twoFactor = $this->getTwoFactor();

            if (!$twoFactor) {
                return;
            }

            $options = $twoFactor->getOptions();
            $key = \array_search($code, $options['backupCodes'] ?? []);

            if (false !== $key) {
                unset($options['backupCodes'][$key]);
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
    }
} else {
    /**
     * @internal
     */
    trait BackupCodeTrait
    {
    }
}
