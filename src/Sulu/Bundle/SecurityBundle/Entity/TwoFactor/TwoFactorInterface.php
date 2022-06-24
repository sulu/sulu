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

use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;

interface TwoFactorInterface extends PreferredProviderInterface, EmailInterface, GoogleInterface, TotpInterface, BackupCodeInterface, TrustedDeviceInterface
{
    public function getTwoFactor(): ?UserTwoFactor;

    /**
     * @return static
     */
    public function setTwoFactor(?UserTwoFactor $twoFactor);
}
