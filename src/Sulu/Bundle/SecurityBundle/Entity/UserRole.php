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
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\SecurityBundle\Exception\AssignAnonymousRoleException;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;

#[ExclusionPolicy('all')]
class UserRole extends ApiEntity
{
    #[Expose]
    protected int $id;

    #[Expose]
    protected string $locale;

    protected UserInterface $user;

    #[Expose]
    protected RoleInterface $role;

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return list<string>|null
     */
    #[VirtualProperty]
    #[SerializedName('locales')]
    public function getLocales(): ?array
    {
        $decoded = \json_decode($this->locale, true);

        if (!\is_array($decoded)) {
            return null;
        }

        /** @var list<string> */
        return \array_values($decoded);
    }

    public function setUser(UserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setRole(RoleInterface $role): static
    {
        if ($role->getAnonymous()) {
            throw new AssignAnonymousRoleException($role);
        }

        $this->role = $role;

        return $this;
    }

    public function getRole(): RoleInterface
    {
        return $this->role;
    }
}
