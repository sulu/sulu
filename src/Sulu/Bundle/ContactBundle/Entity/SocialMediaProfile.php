<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

#[ExclusionPolicy('All')]
class SocialMediaProfile
{
    private int $id;

    private string $username;

    private SocialMediaProfileType $socialMediaProfileType;

    /**
     * @var Collection<int, ContactInterface>
     */
    private Collection $contacts;

    /**
     * @var Collection<int, AccountInterface>
     */
    private Collection $accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->accounts = new ArrayCollection();
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    public function getId(): int
    {
        return $this->id;
    }

    public function setUsername(string $username): static
    {
        // Limit to maximal sql column length.
        $this->username = \substr($username, 0, 255);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('username')]
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setSocialMediaProfileType(SocialMediaProfileType $socialMediaProfileType): static
    {
        $this->socialMediaProfileType = $socialMediaProfileType;

        return $this;
    }

    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('socialMediaProfileType')]
    #[Groups(['fullAccount', 'fullContact'])]
    public function getSocialMediaProfileType(): SocialMediaProfileType
    {
        return $this->socialMediaProfileType;
    }

    public function addContact(ContactInterface $contacts): static
    {
        $this->contacts[] = $contacts;

        return $this;
    }

    public function removeContact(ContactInterface $contacts): static
    {
        $this->contacts->removeElement($contacts);

        return $this;
    }

    /**
     * @return Collection<int, ContactInterface>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addAccount(AccountInterface $account): static
    {
        $this->accounts[] = $account;

        return $this;
    }

    public function removeAccount(AccountInterface $account): static
    {
        $this->accounts->removeElement($account);

        return $this;
    }

    /**
     * @return Collection<int, AccountInterface>
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }
}
