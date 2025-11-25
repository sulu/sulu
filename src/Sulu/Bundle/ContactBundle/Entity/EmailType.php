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

class EmailType implements \JsonSerializable
{
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private string $name;

    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private int $id;

    /**
     * @var Collection<int, Email>
     */
    #[Exclude]
    private Collection $emails;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->emails = new ArrayCollection();
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

    public function addEmail(Email $emails): static
    {
        $this->emails[] = $emails;

        return $this;
    }

    public function removeEmail(Email $emails): static
    {
        $this->emails->removeElement($emails);

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
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
