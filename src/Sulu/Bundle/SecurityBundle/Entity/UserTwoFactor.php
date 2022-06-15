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
use JMS\Serializer\Annotation\VirtualProperty;
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
     */
    private ?string $options = null;

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
     * @VirtualProperty()
     * @Groups({"profile"})
     *
     * @return mixed[]
     */
    public function getOptions(): ?array
    {
        if (null === $this->options) {
            return null;
        }

        return \json_decode($this->options, true, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed[] $options
     *
     * @return static
     */
    public function setOptions(?array $options)
    {
        $this->options = $options ? \json_encode($options, true, \JSON_THROW_ON_ERROR) : null;

        return $this;
    }
}
