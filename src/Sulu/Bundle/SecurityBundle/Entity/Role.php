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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Role
 * @ExclusionPolicy("all");
 */
class Role extends ApiEntity
{

    /**
     * @var string
     * @Expose
     */
    private $name;

    /**
     * @var string
     * @Expose
     */
    private $system;

    /**
     * @var \DateTime
     * @Expose
     */
    private $created;

    /**
     * @var \DateTime
     * @Expose
     */
    private $changed;

    /**
     * @var integer
     * @Expose
     */
    private $id;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     * @Expose
     */
    private $creator;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     * @Expose
     */
    private $changer;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Expose
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userRoles;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\SecurityType
     */
    private $securityType;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set system
     *
     * @param string $system
     * @return Role
     */
    public function setSystem($system)
    {
        $this->system = $system;

        return $this;
    }

    /**
     * Get system
     *
     * @return string
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Role
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Role
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
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
     * Set creator
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $creator
     * @return Role
     */
    public function setCreator(\Sulu\Bundle\SecurityBundle\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $changer
     * @return Role
     */
    public function setChanger(\Sulu\Bundle\SecurityBundle\Entity\User $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Add permissions
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     * @return Role
     */
    public function addPermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     */
    public function removePermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Role $userRoles
     * @return Role
     */
    public function addUserRole(\Sulu\Bundle\SecurityBundle\Entity\Role $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    /**
     * Remove userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Role $userRoles
     */
    public function removeUserRole(\Sulu\Bundle\SecurityBundle\Entity\Role $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    /**
     * Get userRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /**
     * Add groups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     * @return Role
     */
    public function addGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     */
    public function removeGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set securityType
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\SecurityType $securityType
     * @return Role
     */
    public function setSecurityType(\Sulu\Bundle\SecurityBundle\Entity\SecurityType $securityType = null)
    {
        $this->securityType = $securityType;
    
        return $this;
    }

    /**
     * Get securityType
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\SecurityType 
     */
    public function getSecurityType()
    {
        return $this->securityType;
    }
}