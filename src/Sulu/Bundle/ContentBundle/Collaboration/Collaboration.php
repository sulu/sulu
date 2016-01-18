<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Collaboration;

/**
 * Represents a user, which is currently editing an entity.
 */
class Collaboration
{
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

    public function __construct($userId, $username, $fullName, $type, $id)
    {
        $this->userId = $userId;
        $this->username = $username;
        $this->fullName = $fullName;
        $this->type = $type;
        $this->id = $id;
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
}
