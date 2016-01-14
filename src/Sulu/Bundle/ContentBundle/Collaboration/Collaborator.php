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

use Ratchet\ConnectionInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Represents a user, which is currently editing an entity.
 */
class Collaborator
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $id;

    public function __construct(UserInterface $user, ConnectionInterface $connection, $type, $id)
    {
        $this->user = $user;
        $this->connection = $connection;
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Returns the user for this collaboration.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns the connection for the user in this collaboration.
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
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
