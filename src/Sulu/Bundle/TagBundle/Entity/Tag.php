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
class Tag implements TagInterface
{
    use UserBlameTrait;

    /**
     * @var string
     */
    #[Expose]
    #[Groups(['partialTag'])]
    private $name;

    /**
     * @var int
     */
    #[Groups(['partialTag'])]
    private $id;

    #[Groups(['partialTag'])]
    private \DateTimeImmutable $created;

    #[Groups(['partialTag'])]
    private \DateTimeImmutable $changed;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(\DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getChanged(): \DateTimeImmutable
    {
        return $this->changed;
    }

    public function setChanged(\DateTimeImmutable $changed): self
    {
        $this->changed = $changed;

        return $this;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }
}
