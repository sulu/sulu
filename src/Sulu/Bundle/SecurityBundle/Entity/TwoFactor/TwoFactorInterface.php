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

interface TwoFactorInterface extends PreferredProviderInterface, EmailInterface, GoogleInterface, TotpInterface, BackupCodeInterface, TrustedDeviceInterface
{
    public function getTwoFactorMethod(): ?string;

    /**
     * @return static
     */
    public function setTwoFactorMethod(?string $twoFactorType);

    /**
     * @return mixed[]|null
     */
    public function getTwoFactorOptions(): ?array;

    /**
     * @param mixed[]|null $twoFactorOptions
     *
     * @return static
     */
    public function setTwoFactorOptions(?array $twoFactorOptions);
}
