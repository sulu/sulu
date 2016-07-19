<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Collaboration;

/**
 * Represents a user, which is currently editing an entity.
 */
class Collaboration
{
    /**
     * @var int
     */
    private $connectionId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $fullName;

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $id;

    /**
     * @var int
     */
    private $changed;

    public function __construct($connectionId, $userId, $username, $fullName, $type, $id)
    {
        $this->connectionId = $connectionId;
        $this->userId = $userId;
        $this->username = $username;
        $this->fullName = $fullName;
        $this->type = $type;
        $this->id = $id;
        $this->changed = time();
    }

    /**
     * Returns the connectionId for the user in this collaboration.
     *
     * @return int
     */
    public function getConnectionId()
    {
        return $this->connectionId;
    }

    /**
     * Returns the userId for the user in this collaboration.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Return the username for the user in this collaboration.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Return the full name for the user in this collaboration.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Returns the type of the entity the user is collaborating on.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the id of the entity the user collaborating on.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the timestamp for the last interaction.
     *
     * @return int
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Sets the timestamp for the last interaction.
     *
     * @param int $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }
}
