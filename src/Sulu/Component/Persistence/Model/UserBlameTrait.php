<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Model;

use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Trait with basic implementation of UserBlameInterface.
 */
trait UserBlameTrait
{
    protected ?UserInterface $creator = null;

    protected ?UserInterface $changer = null;

    /**
     * @see UserBlameInterface::getCreator()
     */
    public function getCreator(): ?UserInterface
    {
        return $this->creator;
    }

    public function setCreator(?UserInterface $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @see UserBlameInterface::getChanger()
     */
    public function getChanger(): ?UserInterface
    {
        return $this->changer;
    }

    public function setChanger(?UserInterface $changer): self
    {
        $this->changer = $changer;

        return $this;
    }
}
