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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorInterface;
use Sulu\Component\Security\Authentication\UserInterface;

#[ExclusionPolicy('all')]
class UserTwoFactor
{
    private int $id;

    private UserInterface $user;

    #[Expose]
    #[Groups(['profile'])]
    private ?string $method = null;

    private ?string $options = null;

    public function __construct(TwoFactorInterface $user)
    {
        /** @var UserInterface $user */
        $this->user = $user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $twoFactorType): static
    {
        $this->method = $twoFactorType;

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('hasBackupCodes')]
    #[Groups(['profile'])]
    public function hasBackupCodes(): bool
    {
        return [] !== ($this->getOptions()['backupCodes'] ?? []);
    }

    /**
     * @return array{
     *     backupCodes?: string[],
     *     authCode?: string,
     *     googleAuthenticatorSecret?: string,
     *     totpSecret?: string,
     *     pendingTotpSecret?: string,
     *     trustedVersion?: int,
     *     googleAuthenticatorUsername?: string,
     *     googleAuthenticatorSecret?: string,
     * }
     */
    public function getOptions(): ?array
    {
        if (null === $this->options) {
            return null;
        }

        /**
         * @var array{
         *     backupCodes?: string[],
         *     authCode?: string,
         *     googleAuthenticatorSecret?: string,
         *     totpSecret?: string,
         *     pendingTotpSecret?: string,
         *     trustedVersion?: int,
         *     googleAuthenticatorUsername?: string,
         *     googleAuthenticatorSecret?: string,
         * }
         */
        return \json_decode($this->options, true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function setOptions(?array $options): static
    {
        $this->options = $options ? \json_encode($options, \JSON_THROW_ON_ERROR) : null;

        return $this;
    }
}
