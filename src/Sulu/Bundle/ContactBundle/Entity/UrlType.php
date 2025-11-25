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

class UrlType implements \JsonSerializable
{
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private string $name;

    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private int $id;

    /**
     * @var Collection<int, Url>
     */
    #[Exclude]
    private Collection $urls;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->urls = new ArrayCollection();
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

    public function addUrl(Url $urls): static
    {
        $this->urls[] = $urls;

        return $this;
    }

    public function removeUrl(Url $urls): static
    {
        $this->urls->removeElement($urls);

        return $this;
    }

    /**
     * @return Collection<int, Url>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
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
