<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Sulu\Component\Security\Authentication\SecurityIdentityInterface;

/**
 * Defines methods for assigning security to objects
 * @package Sulu\Component\Security\Authorization\AccessControl
 */
interface AccessControlManagerInterface
{
    /**
     * Sets the permissions for the object with the given class and id for the given security identity
     * @param string $class The name of the class to protect
     * @param string $identifier
     * @param SecurityIdentityInterface $securityIdentity
     * @param $permissions
     */
    public function setPermissions($class, $identifier, SecurityIdentityInterface $securityIdentity, $permissions);
}
