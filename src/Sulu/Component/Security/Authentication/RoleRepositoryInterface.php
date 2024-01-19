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

/**
 * @extends RepositoryInterface<RoleInterface>
 */
interface RoleRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds a role with a specific id.
     *
     * @param int $id id of the role
     *
     * @return RoleInterface|null
     */
    public function findRoleById($id);

    /**
     * Searches for all roles.
     *
     * @param array $filter = []
     *
     * @return RoleInterface[]
     */
    public function findAllRoles(array $filter = []);

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
