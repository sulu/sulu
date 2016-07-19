<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Event;

use Symfony\Component\EventDispatcher\Event;

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
     * @var string
     */
    private $securityIdentity;

    /**
     * @param string $type
     * @param string $identifier
     * @param string $securityIdentity
     */
    public function __construct($type, $identifier, $securityIdentity)
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->securityIdentity = $securityIdentity;
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
     * Returns the security identifier for which the permissions have been updated.
     *
     * @return string
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }
}
