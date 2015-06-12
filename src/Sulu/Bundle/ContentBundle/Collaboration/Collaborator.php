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

/**
 * Represents a user, which is currently editing an entity.
 */
class Collaborator
{
    /**
     * @var int
     */
    private $user;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct($user, ConnectionInterface $connection)
    {
        $this->user = $user;
        $this->connection = $connection;
    }

    /**
     * Returns the user for this collaboration.
     *
     * @return int
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
}
