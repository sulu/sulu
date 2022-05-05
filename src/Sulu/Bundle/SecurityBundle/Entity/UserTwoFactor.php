<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorInterface;

class UserTwoFactor
{
    private int $id;

    private TwoFactorInterface $user;

    /**
     * @Expose
     * @Groups({"profile"})
     */
    private ?string $method = null;

    /**
     * @Expose
     * @Groups({"profile"})
     *
     * @var mixed[]|null
     */
    private ?array $options = null;

    public function __construct(TwoFactorInterface $user)
    {
        $this->user = $user;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return static
     */
    public function setMethod(?string $twoFactorType)
    {
        $this->method = $twoFactorType;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param mixed[] $options
     *
     * @return static
     */
    public function setOptions(?array $options)
    {
        $this->options = $options;

        return $this;
    }
}
