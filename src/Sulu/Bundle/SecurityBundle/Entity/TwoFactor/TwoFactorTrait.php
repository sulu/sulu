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

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * @internal
 */
trait TwoFactorTrait
{
    use BackupCodeTrait;
    use EmailTrait;
    use GoogleTrait;
    use PreferredProviderTrait;
    use TotpTrait;
    use TrustedDeviceTrait;

    /**
     * @Expose
     * @Groups({"profile"})
     */
    private ?string $twoFactorMethod = null;

    /**
     * @Expose
     * @Groups({"profile"})
     *
     * @var mixed[]|null
     */
    private ?array $twoFactorOptions = null;

    public function getTwoFactorMethod(): ?string
    {
        return $this->twoFactorMethod;
    }

    /**
     * @return static
     */
    public function setTwoFactorMethod(?string $twoFactorType)
    {
        $this->twoFactorMethod = $twoFactorType;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getTwoFactorOptions(): ?array
    {
        return $this->twoFactorOptions;
    }

    /**
     * @param mixed[] $twoFactorOptions
     *
     * @return static
     */
    public function setTwoFactorOptions(?array $twoFactorOptions)
    {
        $this->twoFactorOptions = $twoFactorOptions;

        return $this;
    }
}
