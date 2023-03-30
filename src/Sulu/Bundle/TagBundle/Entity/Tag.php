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
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Represents single tag in the system.
 */
class Tag implements TagInterface
{
    /**
     * @Expose
     * @Groups({"partialTag"})
     */
    private ?string $name = null;

    /**
     * @Groups({"partialTag"})
     */
    private ?int $id = null;

    /**
     * @Groups({"partialTag"})
     */
    private ?\DateTime $created = null;

    /**
     * @Groups({"partialTag"})
     */
    private ?\DateTime $changed = null;

    private ?\Sulu\Component\Security\Authentication\UserInterface $changer = null;

    private ?\Sulu\Component\Security\Authentication\UserInterface $creator = null;

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

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return $this
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @return $this
     */
    public function setChanged(\DateTime $changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * @return $this
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @return $this
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
