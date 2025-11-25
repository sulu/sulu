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

class PhoneType implements \JsonSerializable
{
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private string $name;

    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private int $id;

    /**
     * @var Collection<int, Phone>
     */
    #[Exclude]
    private Collection $phones;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->phones = new ArrayCollection();
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

    public function addPhone(Phone $phones): static
    {
        $this->phones[] = $phones;

        return $this;
    }

    public function removePhone(Phone $phones): static
    {
        $this->phones->removeElement($phones);

        return $this;
    }

    /**
     * @return Collection<int, Phone>
     */
    public function getPhones(): Collection
    {
        return $this->phones;
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
}
