<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Permission
 */
class Permission
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var integer
     */
    private $permissions;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\Role
     */
    private $role;

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
     * @param \Sulu\Bundle\SecurityBundle\Entity\Role $role
     * @return Permission
     */
    public function setRole(\Sulu\Bundle\SecurityBundle\Entity\Role $role = null)
    {
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\Role 
     */
    public function getRole()
    {
        return $this->role;
    }
}