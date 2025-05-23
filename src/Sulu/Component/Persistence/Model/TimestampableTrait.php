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

/**
 * Trait with basic implementation for the TimestampableInterface.
 *
 * @see TimestampableInterface
 */
trait TimestampableTrait
{
    protected \DateTimeImmutable $created;

    protected \DateTimeImmutable $changed;

    /**
     * @see TimestampableInterface::getCreated()
     */
    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(\DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @see TimestampableInterface::getChanged()
     */
    public function getChanged(): \DateTimeImmutable
    {
        return $this->changed;
    }

    public function setChanged(\DateTimeImmutable $changed): self
    {
        $this->changed = $changed;

        return $this;
    }
}
