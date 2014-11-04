<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * Permission
 * @ExclusionPolicy("all");
 */
class Permission
{
    /**
     * @var string
     * @Expose
     */
    private $context;

    /**
     * @var integer
     * @Expose
     */
    private $permissions;

    /**
     * @var integer
     * @Expose
     */
    private $id;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\RoleInterface
     */
    private $role;

    /**
     * @var string
     * @Expose
     */
    private $module;

    /**
     * Set context
     *
     * @param string $context
     * @return Permission
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set permissions
     *
     * @param integer $permissions
     * @return Permission
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * Get permissions
     *
     * @return integer
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set role
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\RoleInterface $role
     * @return Permission
     */
    public function setRole(\Sulu\Bundle\SecurityBundle\Entity\RoleInterface $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set module
     *
     * @param string $module
     * @return Permission
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }
}
