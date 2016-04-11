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

use Sulu\Component\Security\Authentication\RoleInterface;

/**
 * Interface for the model responsible for storing access control information of objects.
 */
interface AccessControlInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return RoleInterface
     */
    public function getRole();

    /**
     * @param RoleInterface $role
     */
    public function setRole($role);

    /**
     * @return int
     */
    public function getPermissions();

    /**
     * @param int $permissions
     */
    public function setPermissions($permissions);

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     */
    public function setEntityId($entityId);

    /**
     * @return mixed
     */
    public function getEntityClass();

    /**
     * @param mixed $entityClass
     */
    public function setEntityClass($entityClass);
}
