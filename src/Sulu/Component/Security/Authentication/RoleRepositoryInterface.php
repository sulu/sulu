<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

interface RoleRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds a role with a specific id.
     *
     * @param int $id id of the role
     *
     * @return RoleInterface
     */
    public function findRoleById($id);

    /**
     * Searches for all roles.
     *
     * @return RoleInterface[]
     */
    public function findAllRoles();

    /**
     * Return an array containing the names of all the roles.
     *
     * @return string[]
     */
    public function getRoleNames();

    /**
     * @return array
     */
    public function findRoleIdsBySystem($system);
}
