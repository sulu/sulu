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

use JMS\Serializer\Annotation\Exclude;

class ContactAddress
{
    private bool $main = false;

    private ?int $id = null;

    private Address $address;

    #[Exclude]
    private ContactInterface $contact;

    public function setMain(bool $main): static
    {
        $this->main = $main;

        return $this;
    }

    public function getMain(): bool
    {
        return $this->main;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
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
}
