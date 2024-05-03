<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Entity;

/**
 * Represents a user, which is currently editing an entity.
 */
class Collaboration
{
    /**
     * @var int
     */
    private $started;

    /**
     * @var int
     */
    private $changed;

    /**
     * @param string $connectionId
     * @param int $userId
     * @param string $username
     * @param string $fullName
     * @param string $resourceKey
     * @param mixed $id
     */
    public function __construct(
        private $connectionId,
        private $userId,
        private $username,
        private $fullName,
        private $resourceKey,
        private $id,
    ) {
        $this->started = \time();
        $this->changed = \time();
    }

    public function getConnectionId()
    {
        return $this->connectionId;
    }

    public function getResourceKey()
    {
        return $this->resourceKey;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStarted()
    {
        return $this->started;
    }

    public function getChanged()
    {
        return $this->changed;
    }

    public function updateTime()
    {
        $this->changed = \time();
    }
}
