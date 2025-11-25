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

class FaxType implements \JsonSerializable
{
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private string $name;

    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private int $id;

    /**
     * @var Collection<int, Fax>
     */
    #[Exclude]
    private Collection $faxes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->faxes = new ArrayCollection();
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

    public function addFaxe(Fax $faxes): static
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    public function removeFaxe(Fax $faxes): static
    {
        $this->faxes->removeElement($faxes);

        return $this;
    }

    /**
     * @return Collection<int, Fax>
     */
    public function getFaxes(): Collection
    {
        return $this->faxes;
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

    public function addFax(Fax $faxes): static
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    public function removeFax(Fax $faxes): static
    {
        $this->faxes->removeElement($faxes);

        return $this;
    }
}
