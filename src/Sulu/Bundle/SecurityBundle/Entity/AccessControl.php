<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\SecurityBundle\Entity;

use Sulu\Component\Security\Authentication\RoleInterface;

/**
 * Model for storing access control information of objects.
 */
class AccessControl
{
    /**
     * @var int
     */
    private $id;

    /**
     * The role this access control rule is valid for.
     *
     * @var RoleInterface
     */
    private $role;

    /**
     * Holds the permissions as a bitmask.
     *
     * @var int
     */
    private $permissions;

    /**
     * The id of the model this access control rule applies to.
     *
     * @var int
     */
    private $entityId;

    /**
     * The class of the model this access control rule applies to.
     *
     * @var string
     */
    private $entityClass;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param RoleInterface $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param int $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * @return mixed
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param mixed $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }
}
