<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;

class CollectionType
{
    private int $id;

    private string $name;

    private ?string $key = null;

    private ?string $description = null;

    /** @var DoctrineCollection<int, CollectionInterface> */
    #[Exclude]
    private DoctrineCollection $collections;

    public function __construct()
    {
        $this->collections = new ArrayCollection();
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * To force id = 1 in load fixtures.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addCollection(CollectionInterface $collections): static
    {
        $this->collections->add($collections);

        return $this;
    }

    public function removeCollection(CollectionInterface $collections): static
    {
        $this->collections->removeElement($collections);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, CollectionInterface>
     */
    public function getCollections(): DoctrineCollection
    {
        return $this->collections;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
