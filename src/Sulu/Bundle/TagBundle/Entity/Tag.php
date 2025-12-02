<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Entity;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\UserBlameTrait;

/**
 * Represents single tag in the system.
 */
class Tag implements \Stringable, TagInterface
{
    use UserBlameTrait;

    #[Expose]
    #[Groups(['partialTag'])]
    private string $name = '';

    #[Groups(['partialTag'])]
    private int $id;

    #[Groups(['partialTag'])]
    private \DateTimeImmutable $created;

    #[Groups(['partialTag'])]
    private \DateTimeImmutable $changed;

    public function __construct()
    {
        $this->created = new \DateTimeImmutable();
        $this->changed = new \DateTimeImmutable();
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

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(\DateTimeImmutable $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getChanged(): \DateTimeImmutable
    {
        return $this->changed;
    }

    public function setChanged(\DateTimeImmutable $changed): static
    {
        $this->changed = $changed;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
