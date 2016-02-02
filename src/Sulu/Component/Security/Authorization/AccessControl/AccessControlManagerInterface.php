<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;

/**
 * Defines methods for assigning security to objects.
 */
interface AccessControlManagerInterface
{
    /**
     * Sets the permissions for the object with the given class and id for the given security identity.
     *
     * @param string $type The type of the protected object
     * @param string $identifier The identifier of the protected object
     * @param $permissions
     */
    public function setPermissions($type, $identifier, $permissions);

    /**
     * Returns the permissions for all security identities.
     *
     * @param string $type The type of the protected object
     * @param string $identifier The identifier of the protected object
     *
     * @return array
     */
    public function getPermissions($type, $identifier);

    /**
     * Returns the permissions regarding an object and its security context for a given user.
     *
     * @param SecurityCondition $securityCondition The condition to check
     * @param UserInterface $user The user for which the security is returned
     *
     * @return array
     */
    public function getUserPermissions(SecurityCondition $securityCondition, UserInterface $user);

    /**
     * Returns the permissions regarding an array of role permissions and its security context for a given user.
     *
     * @param string $locale
     * @param string $securityContext
     * @param $objectPermissionsByRole
     * @param UserInterface $user The user for which the security is returned
     *
     * @return array
     */
    public function getUserPermissionByArray($locale, $securityContext, $objectPermissionsByRole, UserInterface $user);
}
