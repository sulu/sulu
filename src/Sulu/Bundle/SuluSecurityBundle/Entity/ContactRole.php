<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactRole
 */
class ContactRole
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $contact;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\Role
     */
    private $role;


    /**
     * Set locale
     *
     * @param string $locale
     * @return ContactRole
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
     * Set contact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contact
     * @return ContactRole
     */
    public function setContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contact)
    {
        $this->contact = $contact;
    
        return $this;
    }

    /**
     * Get contact
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set role
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Role $role
     * @return ContactRole
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
