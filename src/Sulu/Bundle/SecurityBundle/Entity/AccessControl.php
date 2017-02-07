<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlInterface;

class AccessControl implements AccessControlInterface
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
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }
}
