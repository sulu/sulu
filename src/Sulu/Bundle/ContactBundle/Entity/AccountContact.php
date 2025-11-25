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

class AccountContact
{
    public const RESOURCE_KEY = 'account_contacts';

    private bool $main = false;

    private int $id;

    private ContactInterface $contact;

    private AccountInterface $account;

    private ?Position $position = null;

    public function setMain(bool $main): static
    {
        $this->main = $main;

        return $this;
    }

    public function getMain(): bool
    {
        return $this->main;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setContact(ContactInterface $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getContact(): ContactInterface
    {
        return $this->contact;
    }

    public function setAccount(AccountInterface $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): AccountInterface
    {
        return $this->account;
    }

    public function setPosition(?Position $position = null): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }
}
