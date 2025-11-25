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
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

class Note
{
    #[Groups(['fullAccount', 'fullContact'])]
    private string $value;

    #[Groups(['fullAccount', 'fullContact'])]
    private int $id;

    /**
     * @var Collection<int, ContactInterface>
     */
    #[Exclude]
    private Collection $contacts;

    /**
     * @var Collection<int, AccountInterface>
     */
    #[Exclude]
    private Collection $accounts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->accounts = new ArrayCollection();
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getId(): int
    {
        return $this->id;
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
