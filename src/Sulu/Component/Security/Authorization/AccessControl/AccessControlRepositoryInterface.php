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

/**
 * Defines methods to retrieve AccessControl models.
 */
interface AccessControlRepositoryInterface
{
    /**
     * Find AccessControl by its entities type and id and the role id.
     *
     * @param string $type The type of the AccessControl
     * @param int $id The id of the AccessControl
     * @param int $roleId The role id of the AccessControl
     *
     * @return AccessControlInterface
     */
    public function findByTypeAndIdAndRole($type, $id, $roleId);

    /**
     * Finds all AccessControls for the given entity type and id.
     *
     * @param string $type The type of the AccessControl
     * @param int $id The id of the AccessControl
     *
     * @return AccessControlInterface[]
     */
    public function findByTypeAndId($type, $id);
}
