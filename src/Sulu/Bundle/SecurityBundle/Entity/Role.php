<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Role
 */
class Role
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $system;

    /**
     * @var string
     */
    private $context;

    /**
     * @var integer
     */
    private $permission;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $contactRoles;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $creator;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $changer;

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
     * Set context
     *
     * @param string $context
     * @return Role
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
     * Set permission
     *
     * @param integer $permission
     * @return Role
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
    
        return $this;
    }

    /**
     * Get permission
     *
     * @return integer 
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contactRoles = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add contactRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\ContactRole $contactRoles
     * @return Role
     */
    public function addContactRole(\Sulu\Bundle\SecurityBundle\Entity\ContactRole $contactRoles)
    {
        $this->contactRoles[] = $contactRoles;
    
        return $this;
    }

    /**
     * Remove contactRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\ContactRole $contactRoles
     */
    public function removeContactRole(\Sulu\Bundle\SecurityBundle\Entity\ContactRole $contactRoles)
    {
        $this->contactRoles->removeElement($contactRoles);
    }

    /**
     * Get contactRoles
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getContactRoles()
    {
        return $this->contactRoles;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $creator
     * @return Role
     */
    public function setCreator(\Sulu\Bundle\ContactBundle\Entity\Contact $creator = null)
    {
        $this->creator = $creator;
    
        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact 
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $changer
     * @return Role
     */
    public function setChanger(\Sulu\Bundle\ContactBundle\Entity\Contact $changer = null)
    {
        $this->changer = $changer;
    
        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact 
     */
    public function getChanger()
    {
        return $this->changer;
    }
}