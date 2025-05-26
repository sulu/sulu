<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Domain\Model;

use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Basic implementation of the AuditableInterface.
 */
trait AuditableTrait
{
    protected \DateTimeImmutable $created;

    protected \DateTimeImmutable $changed;

    protected ?UserInterface $creator = null;

    protected ?UserInterface $changer = null;

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

    public function getCreator(): ?UserInterface
    {
        return $this->creator;
    }

    public function setCreator(?UserInterface $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

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
