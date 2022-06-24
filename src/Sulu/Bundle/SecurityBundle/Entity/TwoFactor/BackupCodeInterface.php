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

use Scheb\TwoFactorBundle\Model\BackupCodeInterface as SchebBackupCodeInterface;

/*
 * Bridge interface to the scheb/2fa-backup-code TwoFactorInterface.
 */
if (\interface_exists(SchebBackupCodeInterface::class)) {
    /**
     * @internal
     */
    interface BackupCodeInterface extends SchebBackupCodeInterface
    {
    }
} else {
    /**
     * @internal
     */
    interface BackupCodeInterface
    {
    }
}
