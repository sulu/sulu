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

class Url
{
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    private string $url;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    private int $id;

    #[Groups(['fullAccount', 'fullContact'])]
    private UrlType $urlType;

    /**
     * @var Collection<int, AccountInterface>
     */
    #[Exclude]
    private Collection $accounts;

    /**
     * @var Collection<int, ContactInterface>
     */
    #[Exclude]
    private Collection $contacts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUrlType(UrlType $urlType): static
    {
        $this->urlType = $urlType;

        return $this;
    }

    public function getUrlType(): UrlType
    {
        return $this->urlType;
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
