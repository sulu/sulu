<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event is dispatched when any object permission have been updated.
 */
class PermissionUpdateEvent extends Event
{
    /**
     * The type of the object for which the permissions have been updated.
     *
     * @var string
     */
    private $type;

    /**
     * The identifier of the object for which the permissions have been updated.
     *
     * @var string
     */
    private $identifier;

    /**
     * The security identity for which the permissions have been updated.
     *
     * @var string
     */
    private $securityIdentity;

    /**
     * The new updated permissions.
     *
     * @var array
     */
    private $permissions;

    /**
     * @param string $type
     * @param string $identifier
     * @param string $securityIdentity
     * @param array $permissions
     */
    public function __construct($type, $identifier, $securityIdentity, $permissions)
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->securityIdentity = $securityIdentity;
        $this->permissions = $permissions;
    }

    /**
     * Returns the type of the object for which the permissions have been updated.
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
     * Returns the security identity for which the permissions have been updated.
     *
     * @return string
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    /**
     * Returns the new updated permissions.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
}
