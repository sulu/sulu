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

class ContactLocale
{
    private string $locale;

    private int $id;

    private ContactInterface $contact;

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
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
}
