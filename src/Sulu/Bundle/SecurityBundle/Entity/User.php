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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorTrait;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

#[ExclusionPolicy('all')]
class User extends ApiEntity implements UserInterface, EquatableInterface, AuditableInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface
{
    use AuditableTrait;
    use TwoFactorTrait;

    #[Expose]
    #[Groups(['frontend', 'fullUser'])]
    protected int $id;

    #[Expose]
    #[Groups(['frontend', 'fullUser', 'profile'])]
    protected string $username = '';

    #[Expose]
    #[Groups(['fullUser', 'profile'])]
    protected ?string $email = null;

    protected string $password = '';

    #[Expose]
    #[Groups(['frontend', 'fullUser', 'profile'])]
    protected string $locale = 'en';

    protected string $salt = '';

    #[Expose]
    protected ?string $privateKey = null;

    protected ?string $apiKey = null;

    #[Expose]
    protected bool $locked = false;

    #[Expose]
    protected bool $enabled = true;

    protected ?\DateTime $lastLogin = null;

    protected ?string $confirmationKey = null;

    protected ?string $passwordResetToken = null;

    private ?\DateTime $passwordResetTokenExpiresAt = null;

    private ?int $passwordResetTokenEmailsSent = null;

    #[Expose]
    #[Groups(['frontend', 'fullUser'])]
    protected ?ContactInterface $contact = null;

    /** @var Collection<int, UserRole> */
    #[Expose]
    protected Collection $userRoles;

    /** @var Collection<string, UserSetting> */
    protected Collection $userSettings;

    public function __construct()
    {
        $this->apiKey = \md5(\uniqid());

        $this->userRoles = new ArrayCollection();
        $this->userSettings = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    #[SerializedName('username')]
    #[Groups(['frontend', 'fullUser'])]
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
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

    public function setSalt(?string $salt): static
    {
        $this->salt = $salt ?? '';

        return $this;
    }

    /**
     * @deprecated The salt functionality was deprecated in Sulu 2.5 and will be removed in Sulu 3.0
     *             Modern password algorithm do not longer require a salt.
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setPrivateKey(?string $privateKey): static
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function eraseCredentials(): void
    {
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setLastLogin(?\DateTime $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setConfirmationKey(?string $confirmationKey): static
    {
        $this->confirmationKey = $confirmationKey;

        return $this;
    }

    public function getConfirmationKey(): ?string
    {
        return $this->confirmationKey;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTime $passwordResetTokenExpiresAt): static
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTime
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenEmailsSent(?int $passwordResetTokenEmailsSent): static
    {
        $this->passwordResetTokenEmailsSent = $passwordResetTokenEmailsSent;

        return $this;
    }

    public function getPasswordResetTokenEmailsSent(): ?int
    {
        return $this->passwordResetTokenEmailsSent;
    }

    public function isEqualTo(SymfonyUserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $this->id === $user->getId()
            && $this->password === $user->getPassword()
            && $this->salt === $user->getSalt()
            && $this->username === $user->getUsername()
            && $this->locked === $user->getLocked()
            && $this->enabled === $user->getEnabled();
    }

    public function addUserRole(UserRole $userRoles): static
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    public function removeUserRole(UserRole $userRoles): static
    {
        $this->userRoles->removeElement($userRoles);

        return $this;
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    #[VirtualProperty]
    #[Groups(['frontend'])]
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->getUserRoles() as $userRole) {
            /* @var UserRole $userRole */
            $roles[] = $userRole->getRole()->getIdentifier();
        }

        return $roles;
    }

    /**
     * @return array<int, RoleInterface>
     */
    public function getRoleObjects(): array
    {
        $roles = [];
        foreach ($this->getUserRoles() as $userRole) {
            $roles[] = $userRole->getRole();
        }

        return $roles;
    }

    public function addUserSetting(UserSetting $userSettings): static
    {
        $this->userSettings[] = $userSettings;

        return $this;
    }

    public function removeUserSetting(UserSetting $userSettings): static
    {
        $this->userSettings->removeElement($userSettings);

        return $this;
    }

    /**
     * @return Collection<string, UserSetting>
     */
    public function getUserSettings(): Collection
    {
        return $this->userSettings;
    }

    /**
     * @return array<string, mixed>
     */
    #[VirtualProperty]
    #[Groups(['frontend'])]
    public function getSettings(): array
    {
        $userSettingValues = [];
        foreach ($this->userSettings as $userSetting) {
            $userSettingValues[$userSetting->getKey()] = \json_decode($userSetting->getValue(), true);
        }

        return $userSettingValues;
    }

    public function setContact(?ContactInterface $contact = null): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getContact(): ?ContactInterface
    {
        return $this->contact;
    }

    #[VirtualProperty]
    #[SerializedName('fullName')]
    #[Groups(['frontend', 'fullUser'])]
    public function getFullName(): string
    {
        return null !== $this->getContact() ?
            $this->getContact()->getFullName() : $this->getUsername();
    }

    #[VirtualProperty]
    #[Groups(['profile'])]
    public function getFirstName(): string
    {
        if (null === $this->contact) {
            return '';
        }

        return $this->contact->getFirstName();
    }

    public function setFirstName(string $firstName): static
    {
        $this->contact?->setFirstName($firstName);

        return $this;
    }

    #[VirtualProperty]
    #[Groups(['profile'])]
    public function getLastName(): string
    {
        if (null === $this->contact) {
            return '';
        }

        return $this->contact->getLastName();
    }

    public function setLastName(string $lastName): static
    {
        $this->contact?->setLastName($lastName);

        return $this;
    }
}
