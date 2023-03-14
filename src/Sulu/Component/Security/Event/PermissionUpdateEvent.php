<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched when the permissions of an object have been updated.
 */
class PermissionUpdateEvent extends Event
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var mixed[]
     */
    private $permissions;

    /**
     * @param string $type
     * @param string $identifier
     * @param mixed[] $permissions
     */
    public function __construct($type, $identifier, $permissions)
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->permissions = $permissions;
    }

    /**
     * Returns the type of object for which the permissions have been updated.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the identifier of the object for which the permissions have been updated.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @deprecated
     *
     * @see PermissionUpdateEvent::getPermissions()
     *
     * Returns the security identifier for which the permissions have been updated.
     *
     * @return mixed[]
     */
    public function getSecurityIdentity()
    {
        return $this->permissions;
    }

    /**
     * @return mixed[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
}
