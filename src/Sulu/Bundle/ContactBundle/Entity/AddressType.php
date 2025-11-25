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

class AddressType implements \JsonSerializable
{
    #[Groups(['frontend', 'fullAccount', 'fullContact'])]
    private string $name;

    #[Groups(['frontend', 'fullAccount', 'fullContact'])]
    private int $id;

    /**
     * @var Collection<int, Address>
     */
    #[Exclude]
    private Collection $addresses;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addAddresse(Address $addresses): static
    {
        $this->addresses[] = $addresses;

        return $this;
    }

    public function removeAddresse(Address $addresses): static
    {
        $this->addresses->removeElement($addresses);

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    /**
     * @return array{id: int, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }

    public function addAddress(Address $addresses): static
    {
        $this->addresses[] = $addresses;

        return $this;
    }

    public function removeAddress(Address $addresses): static
    {
        $this->addresses->removeElement($addresses);

        return $this;
    }
}
