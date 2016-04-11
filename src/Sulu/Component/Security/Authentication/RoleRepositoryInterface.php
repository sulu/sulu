<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @return array
     */
    public function findAllRoles();

    /**
     * Return an array containing the names of all the roles.
     *
     * @return array
     */
    public function getRoleNames();
}
