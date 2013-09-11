<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRole
 */
class UserRole
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $user;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\Role
     */
    private $role;


    /**
     * Set locale
     *
     * @param string $locale
     * @return UserRole
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set user
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $user
     * @return UserRole
     */
    public function setUser(\Sulu\Bundle\SecurityBundle\Entity\User $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set role
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Role $role
     * @return UserRole
     */
    public function setRole(\Sulu\Bundle\SecurityBundle\Entity\Role $role)
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
