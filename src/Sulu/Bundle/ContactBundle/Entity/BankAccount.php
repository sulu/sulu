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

class BankAccount
{
    private ?string $bankName = null;

    private ?string $bic = null;

    private string $iban;

    private bool $public = false;

    private ?int $id = null;

    /**
     * @var Collection<int, AccountInterface>
     */
    #[Exclude]
    private Collection $accounts;

    /**
     * @var Collection<int, ContactInterface>
     */
    private Collection $contacts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function setBic(?string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setIban(string $iban): static
    {
        $this->iban = $iban;

        return $this;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addAccount(AccountInterface $accounts): static
    {
        $this->accounts[] = $accounts;

        return $this;
    }

    public function removeAccount(AccountInterface $accounts): static
    {
        $this->accounts->removeElement($accounts);

        return $this;
    }

    /**
     * @return Collection<int, AccountInterface>
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function setBankName(?string $bankName): static
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
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
}
