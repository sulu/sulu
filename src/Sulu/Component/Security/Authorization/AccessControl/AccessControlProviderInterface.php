<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

interface AccessControlProviderInterface
{
    public const SERVICE_TAG = 'sulu.access_control';

    /**
     * Sets the permissions for the object with the given class and id for the given security identity.
     *
     * @param string $type The name of the class to protect
     * @param string $identifier
     * @param mixed[] $permissions
     */
    public function setPermissions($type, $identifier, $permissions);

    /**
     * Returns the permissions for all security identities.
     *
     * @param string $type The type of the protected object
     * @param string $identifier The identifier of the protected object
     * @param string $system The security system for filtering the permissions
     *
     * @return array
     */
    public function getPermissions($type, $identifier, $system = null);

    /**
     * Returns whether this provider supports the given type.
     *
     * @param string $type The name of the class protect
     *
     * @return bool
     */
    public function supports($type);
}
